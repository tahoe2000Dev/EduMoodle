require(['jquery'], function($){
    
    MQ = MathQuill.getInterface(2)
    const buttons = {
        '\\frac{a}{b}': '\\frac',
        'a^n': '^',
        '\\sqrt{a}': '\\sqrt',
        };
    let editorId = 0;

    function mathEditor (elem) {
        this.id = editorId++;
        this.$ContainerElem = $(elem);
        this.create()
    }

    mathEditor.prototype = {
        constructor: mathEditor,

        _initDom: function () {
            var self = this
            var $ContainerElem = this.$ContainerElem
            $ContainerElem.css('position','relative')
            let $Editor, $buttons

            $Editor = $('<div class="mathquill-editor"></div>').css('position', 'relative').css('display', 'inline-block').css('top', 5)
            this.$Editor = $Editor
            this._getEditorPos()
            $buttons = $('<div class="buttons" button_id="' + self.id + '" style="display:none;"></div>')
                        .css('position', 'absolute').css('left', 0).css('z-index', '999')
                        .on('click', function(event){
                            event.stopPropagation()
                        })

            $ContainerElem.append($Editor)
            $ContainerElem.append($Editor).append($buttons);

            
            this.$buttons = $buttons

        },

        _toggle: function() {
            var self = this
            self.$Editor.on('click focus', function(event){
                event.stopPropagation()
                self.$buttons.show()
                $('.buttons').each(function(){
                    if($(this).attr('button_id')!=self.id) $(this).hide()
                })
            })
            $('body').on('click', function(event){
                self.$buttons.hide()
            })
        },

        _getEditorPos: function () {
            var self = this
            var $editor = self.$Editor
            var x = $editor.position().left
            var y = $editor.position().top
            var control_x = $editor.outerWidth()
            var control_y = $editor.clientHeight
            self.layer_x = control_x + "px"
            self.layer_y = control_y
        },

        _initEditor: function () {
            var self = this
            var editor = MQ.MathField(this.$Editor[0], {
                handlers: {
                    edit: function() {
                        
                    },
                    enter: function() {

                    }
                }
            })
            function restrictInput(exp) {
                var latex = editor.latex()
                if(exp=='^'){
                    exp = '\\^'
                } else {
                    exp = exp.replace('\\',"\\\\")
                }
                var reg = new RegExp(exp, "g")
                return latex.match(reg)?latex.match(reg).length:0
            }
            Object.keys(buttons).forEach(function (lable) {
                var $button = $('<button class="mathquill-editor-button" type="button"></button>')

                $button.text(lable);
                $button.on('click', function () {
                    if(restrictInput(buttons[lable])<3){
                        editor.cmd(buttons[lable])
                        editor.focus()
                    }else {
                    
                    }
                    
                });
                MQ.StaticMath($button[0]);
                self.$buttons.append($button);
                
            });
            this.editor = editor;
        },
        
        getResult: function() {
            var result = this.editor.latex();
            return result;
        },

        create: function () {
            this._initDom();
            this._initEditor();
            this._toggle();
        }
    }


    _keyStr = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
    var _utf8_decode = function (utftext) {  
        var string = "";  
        var i = 0;  
        var c = c1 = c2 = 0;  
        while ( i < utftext.length ) {  
            c = utftext.charCodeAt(i);  
            if (c < 128) {  
                string += String.fromCharCode(c);  
                i++;  
            } else if((c > 191) && (c < 224)) {  
                c2 = utftext.charCodeAt(i+1);  
                string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));  
                i += 2;  
            } else {  
                c2 = utftext.charCodeAt(i+1);  
                c3 = utftext.charCodeAt(i+2);  
                string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));  
                i += 3;  
            }  
        }  
        return string;  
    }
    var decode64 = function (input) {  
        var output = "";  
        var chr1, chr2, chr3;  
        var enc1, enc2, enc3, enc4;  
        var i = 0;  
        input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");  
        while (i < input.length) {  
            enc1 = _keyStr.indexOf(input.charAt(i++));  
            enc2 = _keyStr.indexOf(input.charAt(i++));  
            enc3 = _keyStr.indexOf(input.charAt(i++));  
            enc4 = _keyStr.indexOf(input.charAt(i++));  
            chr1 = (enc1 << 2) | (enc2 >> 4);  
            chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);  
            chr3 = ((enc3 & 3) << 6) | enc4;  
            output = output + String.fromCharCode(chr1);  
            if (enc3 != 64) {  
                output = output + String.fromCharCode(chr2);  
            }  
            if (enc4 != 64) {  
                output = output + String.fromCharCode(chr3);  
            }  
        }  
        output = _utf8_decode(output);  
        return output;  
    }  







    $(document).ready(function(){
        if($('input[is_original_input=true]').length > 0){
            $('input[is_original_input=true]').each(function(){
                $('<span class="matheditor"></span>').insertBefore($(this));
            })
            $('.matheditor').each(function(){
                var $self = $(this);
                var m = new mathEditor($self);
                var $original_input = $self.next('input');
                var $user_input = $original_input.nextAll('input[name$=userinput]');
            
                if($original_input.val()){
                    m.editor.latex($original_input.val());
                }
            
                $self.on('change keydown keypress keyup', function(){
                    setTimeout(function(){
                        $original_input.val(m.getResult());
                    });
                })

            })
        } else {
            var $finish_button = $('input[name=next]').length ? $('input[name=next]') : $('input[name=finish]');
            $('.matheditor').each(function(){
                var $self = $(this);
                var m = new mathEditor($self);
                var $original_input = $self.next('input');
                var $user_input = $original_input.nextAll('input[name$=userinput]');
                if($user_input.val()){
                    $original_input.val($user_input.val());
                    m.editor.latex($user_input.val());
                } else if($original_input.val()){
                    m.editor.latex($original_input.val());
                }
                
                if($original_input.attr('readonly') == 'readonly'){
                    $self.find('textarea').attr('disabled', 'disabled');
                    $self.find('div').attr('contenteditable', 'false');
                    m.$Editor.off('click focus');
                    m.$Editor.css('background', '#eceeef');
                }
                $self.on('change keydown keypress keyup', function(){
                    setTimeout(function(){
                        $user_input.val(m.getResult());
                    });
                })
                
                var correct_answers = $original_input.nextAll('span[id$=ca]').text();
                correct_answers = $.parseJSON(decode64(correct_answers));
                $finish_button.on('click', function () {
                    $user_input.val(m.getResult());
                    $original_input.val($user_input.val());
                    try {
                        var input_mathexp = MathExpression.fromLatex($user_input.val());
                        $.each(correct_answers, function(i, val){
                            var correct_answer = MathExpression.fromLatex(val.answer);
                            if(correct_answer.equals(input_mathexp)){                        
                                $original_input.val(val.answer);
                                return false;
                            }
                        })
                    } catch(TypeError) {
                        
                    }
                    
                    
                })


            })
        }
        
    })


})