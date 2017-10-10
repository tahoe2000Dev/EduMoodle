
M.qtype_shortanswer = M.qtype_shortanswer || {};


M.qtype_shortanswer.init = function (Y) {
    
    function summary (str) {
        var cleand_str = str.replace(/(\+|-|=)/g, function (msign) {
            return ' ' + msign + ' ';
        })
        cleand_str = cleand_str.replace(/\s+/g, ' ');
        cleand_str = cleand_str.replace(/^\s+(\+|-)\s+/g, function (msignleft) {
            return msignleft[1];
        })
        return cleand_str;
    }

    Y.all('input[clean=true]').each(function (inputobj) {
        inputobj.set('value', summary(inputobj.get('value')));
    })
    
};
