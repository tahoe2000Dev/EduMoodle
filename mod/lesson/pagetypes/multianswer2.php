<?php


/**
 * Multianswer2
 *
 * @package mod_lesson
 *
 **/

defined('MOODLE_INTERNAL') || die();

 /** Multianswer question type */
define("LESSON_PAGE_MULTIANSWER2",   "6");

class lesson_page_type_multianswer2 extends lesson_page {

    protected $type = lesson_page::TYPE_QUESTION;
    protected $typeidstring = 'multianswer2';
    protected $typeid = LESSON_PAGE_MULTIANSWER2;
    protected $string = null;

    public function get_typeid() {
        return $this->typeid;
    }
    public function get_typestring() {
        if ($this->string===null) {
            $this->string = get_string($this->typeidstring, 'lesson');
        }
        return $this->string;
    }
    public function get_idstring() {
        return $this->typeidstring;
    }
    
    public function display($renderer, $attempt) {
        global $USER, $CFG, $PAGE;
        $output = '';

        $contents = $this->get_contents();
        $hasattempt = false;
        $attrs = array('type'=>'text', 'size'=>'50', 'maxlength'=>'200', 'class'=>'form-control');

        if (isset($USER->modattempts[$this->lesson->id])) {
            $attrs['answer'] = s($attempt->useranswer);
        }

        if (isset($this->_customdata['lessonid'])) {
            $lessonid = $this->_customdata['lessonid'];
            if (isset($USER->modattempts[$lessonid]->useranswer)) {
                $attrs['readonly'] = 'readonly';
                $hasattempt = true;
            }
        }
        
        $question = $contents;
        if (preg_match_all('/_____+|====+|\*\*\*\*+/', $contents, $matches)) {
            foreach($matches[0] as $i=>$match) {
                unset($attrs['hidden']);
                $matheditor = '';
                $fieldprefix = 'sub' . $i . '_';
                $fieldname = $fieldprefix . 'answer';
                $attrs['name'] = $fieldname;
                $attrs['id'] = $fieldname;
                $attrs['size'] = round(strlen($match) * 1.1);
                if(strstr($match, '=')){
                    $correctanswers = array();
                    $answers = $this->get_answers();
                    foreach ($answers as $answer) {
                        $answer = parent::rewrite_answers_urls($answer, false);
                        $answer  = clean_param($answer->answer, PARAM_TEXT);
                        $ans = preg_split('/;/', $answer);
                        
                        $correctanswers[] = $ans[$i];
                    }
                    $correctanswer = html_writer::tag('span', base64_encode(json_encode($correctanswers)), array('id' => 'ca', 'hidden' => 'hidden'));
                    $attrs['hidden'] = 'hidden';
                    $matheditor = html_writer::tag('span', '', array('class' => 'matheditor', 'id'=>$fieldname));
                    $input = $matheditor . $input . $correctanswer;
                }
                $input = html_writer::empty_tag('input', $attrs);
                if(strstr($match, '=')){
                    $input = $matheditor . $input . $correctanswer;
                }
                $question = preg_replace('/_____+|====+|\*\*\*\*+/', $input, $question, 1);
            }
        }
        

        $questionfield = html_writer::tag('div', html_writer::tag('div', '', array('class'=>'col-md-3')) . html_writer::tag('div', $question, array('class'=>'col-md-8 form-inline felement')), array('class'=>'row fitem'));
        $questionfield = html_writer::tag('fieldset', $questionfield, array('id'=>'id_pageheader', 'class'=>'clearfix'));


        $disableinputfields = '';
        $disableinputfields .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'id', 'value'=>$PAGE->cm->id));
        $disableinputfields .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'pageid', 'value'=>$this->properties->id));
        $disableinputfields .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'sesskey', 'value'=>sesskey()));
        $disableinputfields .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'_qf__lesson_display_answer_form_shortanswer', 'value'=>'1'));
        
        if ($hasattempt) {
            $control = html_writer::empty_tag('input', array('value'=>get_string("nextpage", "lesson"), 'type'=>'submit', 'class'=>'btn btn-primary',
                                                            'id'=>'id_submitbutton', 'name'=>'submitbutton'
                                                        ));
        } else {
            $control = html_writer::empty_tag('input', array('value'=>get_string("nextpage", "lesson"), 'type'=>'submit', 'class'=>'btn btn-primary',
                                                            'id'=>'id_submitbutton', 'name'=>'submitbutton'
                                                        ));
        }
        $formcontrol = html_writer::tag('div', html_writer::tag('div', '', array('class'=>'col-md-3')) . html_writer::tag('div', $control, array('class'=>'col-md-8 form-inline felement')), array('class'=>'row fitem'));

        $output .= html_writer::tag('form', $disableinputfields . $questionfield . $formcontrol, array('method'=>'post', 'class'=>'mform', 'action'=>$CFG->wwwroot.'/mod/lesson/continue.php', 'id'=>'responseform'));

        $PAGE->requires->js('/question/type/shortanswer2/mathquill-editor/jquery-3.2.1.min.js');
        $PAGE->requires->js('/question/type/shortanswer2/mathquill-editor/mathquill/mathquill-basic.js');
        $PAGE->requires->js('/question/type/shortanswer2/mathquill-editor/math-expressions.js');
        $PAGE->requires->js('/mod/lesson/mathquill-editor.js');

        // Trigger an event question viewed.
        $eventparams = array(
            'context' => context_module::instance($PAGE->cm->id),
            'objectid' => $this->properties->id,
            'other' => array(
                'pagetype' => $this->get_typestring()
                )
            );

        $event = \mod_lesson\event\question_viewed::create($eventparams);
        $event->trigger();
        return $output;
    }

    public function subquestion() {

    }
   
    public function check_answer() {
        global $CFG;
        $result = parent::check_answer();

        require_sesskey();
        $studentanswers = array();
        foreach($_POST as $key=>$value){
            if(preg_match('/^sub(\d+)_answer$/', $key)) {
                $studentanswers[] = $value;
            }
        }
        if ($studentanswers === '') {
            $result->noanswer = true;
            return $result;
        }


        // 不考虑正则，每道题只有一组答案，用 ; 分割，每个答案的不同可能用 ## 分割，主要用在多个空位置可交换的情况做判断

        $answers = $this->get_answers();
        $ismatch = true;
        $newpageid = 0;
        foreach ($answers as $answer) {
            $answer = parent::rewrite_answers_urls($answer, false);
            $newpageid = $answer->jumpto;
            $answer  = clean_param($answer->answer, PARAM_TEXT);
            $ans = preg_split('/;/', $answer);
            $tempans = array();
            $tempanswers = array();
            foreach($ans as $i=>$anss) {      
                $ansss = preg_split('/##/', $anss);
                if(count($ansss)>1){
                    foreach($ansss as $index=>$anssss){
                        $ansss[$index] = $this->clean_answer($anssss);
                    }        
                    if(!in_array($this->clean_answer($studentanswers[$i]), $ansss)) {
                        $ismatch = false;
                        break 2;
                    }
                    if(in_array($this->clean_answer($studentanswers[$i]), $tempanswers)){
                        $ismatch = false;
                        break 2;
                    }
                    $tempanswers[] = $this->clean_answer($studentanswers[$i]);
                } else {
                    if(!(($anss == $studentanswers[$i])||$this->clean_answer($anss) == $this->clean_answer($studentanswers[$i]))){
                        $ismatch = false;
                        break 2;
                    }
                }
            }
        }
        if ($ismatch) {
            $result->newpageid = $newpageid;
            $options = new stdClass();
            $options->para = false;
            $result->response = format_text($answer->response, $answer->responseformat, $options);
            $result->answerid = $answer->id;
        }

        $result->userresponse = $studentanswers;
        //clean student answer as it goes to output.
        $result->studentanswer = s($studentanswers);
        return $result;



    }

    public static function clean_answer($string) {
        $string = strtolower($string);
        $string = preg_replace('/(\+|-|=)/', ' $1 ', $string);
        $string = preg_replace('/(\s+)/', ' ', $string);
        $string = preg_replace('/^\s+(\+|-)\s+/', '$1', $string);
        return $string;  
    }

    public function option_description_string() {
        if ($this->properties->qoption) {
            return " - ".get_string("casesensitive", "lesson");
        }
        return parent::option_description_string();
    }

    public function display_answers(html_table $table) {
        $answers = $this->get_answers();
        $options = new stdClass;
        $options->noclean = true;
        $options->para = false;
        $i = 1;
        foreach ($answers as $answer) {
            $answer = parent::rewrite_answers_urls($answer, false);
            $cells = array();
            if ($this->lesson->custom && $answer->score > 0) {
                // if the score is > 0, then it is correct
                $cells[] = '<span class="labelcorrect">'.get_string("answer", "lesson")." $i</span>: \n";
            } else if ($this->lesson->custom) {
                $cells[] = '<span class="label">'.get_string("answer", "lesson")." $i</span>: \n";
            } else if ($this->lesson->jumpto_is_correct($this->properties->id, $answer->jumpto)) {
                // underline correct answers
                $cells[] = '<span class="correct">'.get_string("answer", "lesson")." $i</span>: \n";
            } else {
                $cells[] = '<span class="labelcorrect">'.get_string("answer", "lesson")." $i</span>: \n";
            }
            $cells[] = format_text($answer->answer, $answer->answerformat, $options);
            $table->data[] = new html_table_row($cells);

            $cells = array();
            $cells[] = "<span class=\"label\">".get_string("response", "lesson")." $i</span>";
            $cells[] = format_text($answer->response, $answer->responseformat, $options);
            $table->data[] = new html_table_row($cells);

            $cells = array();
            $cells[] = "<span class=\"label\">".get_string("score", "lesson").'</span>';
            $cells[] = $answer->score;
            $table->data[] = new html_table_row($cells);

            $cells = array();
            $cells[] = "<span class=\"label\">".get_string("jump", "lesson").'</span>';
            $cells[] = $this->get_jump_name($answer->jumpto);
            $table->data[] = new html_table_row($cells);
            if ($i === 1){
                $table->data[count($table->data)-1]->cells[0]->style = 'width:20%;';
            }
            $i++;
        }
        return $table;
    }
    public function stats(array &$pagestats, $tries) {
        if(count($tries) > $this->lesson->maxattempts) { // if there are more tries than the max that is allowed, grab the last "legal" attempt
            $temp = $tries[$this->lesson->maxattempts - 1];
        } else {
            // else, user attempted the question less than the max, so grab the last one
            $temp = end($tries);
        }
        if (isset($pagestats[$temp->pageid][$temp->useranswer])) {
            $pagestats[$temp->pageid][$temp->useranswer]++;
        } else {
            $pagestats[$temp->pageid][$temp->useranswer] = 1;
        }
        if (isset($pagestats[$temp->pageid]["total"])) {
            $pagestats[$temp->pageid]["total"]++;
        } else {
            $pagestats[$temp->pageid]["total"] = 1;
        }
        return true;
    }

    public function report_answers($answerpage, $answerdata, $useranswer, $pagestats, &$i, &$n) {
        global $PAGE;

        $answers = $this->get_answers();
        $formattextdefoptions = new stdClass;
        $formattextdefoptions->para = false;  //I'll use it widely in this page
        foreach ($answers as $answer) {
            $answer = parent::rewrite_answers_urls($answer, false);
            if ($useranswer == null && $i == 0) {
                // I have the $i == 0 because it is easier to blast through it all at once.
                if (isset($pagestats[$this->properties->id])) {
                    $stats = $pagestats[$this->properties->id];
                    $total = $stats["total"];
                    unset($stats["total"]);
                    foreach ($stats as $valentered => $ntimes) {
                        $data = '<input type="text" size="50" disabled="disabled" class="form-control" ' .
                                'readonly="readonly" value="'.s($valentered).'" />';
                        $percent = $ntimes / $total * 100;
                        $percent = round($percent, 2);
                        $percent .= "% ".get_string("enteredthis", "lesson");
                        $answerdata->answers[] = array($data, $percent);
                    }
                } else {
                    $answerdata->answers[] = array(get_string("nooneansweredthisquestion", "lesson"), " ");
                }
                $i++;
            } else if ($useranswer != null && ($answer->id == $useranswer->answerid || $answer == end($answers))) {
                 // get in here when what the user entered is not one of the answers
                $data = '<input type="text" size="50" disabled="disabled" class="form-control" ' .
                        'readonly="readonly" value="'.s($useranswer->useranswer).'">';
                if (isset($pagestats[$this->properties->id][$useranswer->useranswer])) {
                    $percent = $pagestats[$this->properties->id][$useranswer->useranswer] / $pagestats[$this->properties->id]["total"] * 100;
                    $percent = round($percent, 2);
                    $percent .= "% ".get_string("enteredthis", "lesson");
                } else {
                    $percent = get_string("nooneenteredthis", "lesson");
                }
                $answerdata->answers[] = array($data, $percent);

                if ($answer->id == $useranswer->answerid) {
                    if ($answer->response == null) {
                        if ($useranswer->correct) {
                            $answerdata->response = get_string("thatsthecorrectanswer", "lesson");
                        } else {
                            $answerdata->response = get_string("thatsthewronganswer", "lesson");
                        }
                    } else {
                        $answerdata->response = $answer->response;
                    }
                    if ($this->lesson->custom) {
                        $answerdata->score = get_string("pointsearned", "lesson").": ".$answer->score;
                    } elseif ($useranswer->correct) {
                        $answerdata->score = get_string("receivedcredit", "lesson");
                    } else {
                        $answerdata->score = get_string("didnotreceivecredit", "lesson");
                    }
                    // We have found the correct answer, do not process any more answers.
                    $answerpage->answerdata = $answerdata;
                    break;
                } else {
                    $answerdata->response = get_string("thatsthewronganswer", "lesson");
                    if ($this->lesson->custom) {
                        $answerdata->score = get_string("pointsearned", "lesson").": 0";
                    } else {
                        $answerdata->score = get_string("didnotreceivecredit", "lesson");
                    }
                }
            }
            $answerpage->answerdata = $answerdata;
        }
        return $answerpage;
    }
}


class lesson_add_page_form_multianswer2 extends lesson_add_page_form_base {
    public $qtype = 'multianswer2';
    public $qtypestring = 'multianswer2';
    protected $answerformat = '';
    protected $responseformat = LESSON_ANSWER_HTML;

    public function custom_definition() {

        $this->_form->addElement('checkbox', 'qoption', get_string('options', 'lesson'), get_string('casesensitive', 'lesson')); //oh my, this is a regex option!
        $this->_form->setDefault('qoption', 0);
        $this->_form->addHelpButton('qoption', 'casesensitive', 'lesson');

        for ($i = 0; $i < $this->_customdata['lesson']->maxanswers; $i++) {
            $this->_form->addElement('header', 'answertitle'.$i, get_string('answer').' '.($i+1));
            // Only first answer is required.
            $this->add_answer($i, null, ($i < 1));
            $this->add_response($i);
            $this->add_jumpto($i, null, ($i == 0 ? LESSON_NEXTPAGE : LESSON_THISPAGE));
            $this->add_score($i, null, ($i===0)?1:0);
        }
    }
}

