<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Short answer question renderer class.
 *
 * @package    qtype
 * @subpackage shortanswer2
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Generates the output for short answer questions.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_shortanswer2_renderer extends qtype_renderer {
    public function formulation_and_controls(question_attempt $qa,
            question_display_options $options) {

        $question = $qa->get_question();
        $currentanswer = $qa->get_last_qt_var('answer');
        $userinputanswer = $qa->get_last_qt_var('userinput');
        $inputname = $qa->get_qt_field_name('answer');
        $inputattributes = array(
            'type' => 'text',
            'name' => $inputname,
            'value' => $currentanswer,
            'id' => $inputname,
            'size' => 80,
            'class' => 'form-control',
        );

        if ($options->readonly) {
            $inputattributes['readonly'] = 'readonly';
        }
        
        $feedbackimg = '';
        if ($options->correctness) {
            $answer = $question->get_matching_answer(array('answer' => $currentanswer));
            if ($answer) {
                $fraction = $answer->fraction;
            } else {
                $fraction = 0;
            }
            $inputattributes['class'] .= ' ' . $this->feedback_class($fraction);
            $feedbackimg = $this->feedback_image($fraction);
        }


        $questiontext = $question->format_questiontext($qa);
        $placeholder = false;
        if (preg_match('/_____+/', $questiontext, $matches)) {
            $placeholder = $matches[0];
            $inputattributes['size'] = round(strlen($placeholder) * 1.1);
        }
        $inputattributes['hidden'] = 'hidden';

        $input = html_writer::empty_tag('input', $inputattributes) . $feedbackimg;

        $matheditor = html_writer::tag('span', '', array('class' => 'matheditor'));
        
        $correctanswer = html_writer::tag('span', base64_encode(json_encode($question->answers)), array('id' => $qa->get_qt_field_name('ca'), 'hidden' => 'hidden'));

        $originalinput = html_writer::empty_tag('input', array('type' => 'text', 'name' => $qa->get_qt_field_name('userinput'), 'id' => $qa->get_qt_field_name('userinput'), 'value' => $userinputanswer, "hidden" => "hidden", 'class' => 'form-control'));

        if ($placeholder) {
            $inputinplace = html_writer::tag('label', get_string('answer'),
                    array('for' => $inputattributes['id'], 'class' => 'accesshide'));
            
            $inputinplace .= $matheditor;
            $inputinplace .= $input;
            $inputinplace .= $correctanswer;
            $inputinplace .= $originalinput;
            
            $questiontext = substr_replace($questiontext, $inputinplace,
                    strpos($questiontext, $placeholder), strlen($placeholder));
        }

        $result = html_writer::tag('div', $questiontext, array('class' => 'qtext'));

        if (!$placeholder) {
            $result .= html_writer::start_tag('div', array('class' => 'ablock form-inline'));
            // $result .= html_writer::tag('label', get_string('answer', 'qtype_shortanswer2',
            //         html_writer::tag('span', $matheditor . $input, array('class' => 'answer'))),
            //         array('for' => $inputattributes['id']));
            $result .= html_writer::tag('label', get_string('answer'));
            $result .= $matheditor;
            $result .= $input;
            $result .= $correctanswer;
            $result .= $originalinput;
            $result .= html_writer::end_tag('div');
        }

        if ($qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag('div',
                    $question->get_validation_error(array('answer' => $currentanswer)),
                    array('class' => 'validationerror'));
        }      
        $this->page->requires->js('/question/type/shortanswer2/mathquill-editor/jquery-3.2.1.min.js');
        $this->page->requires->js('/question/type/shortanswer2/mathquill-editor/mathquill/mathquill-basic.js');
        $this->page->requires->js('/question/type/shortanswer2/mathquill-editor/math-expressions.js');
        $this->page->requires->js('/question/type/shortanswer2/mathquill-editor/mathquill-editor.js');
        
        return $result;
    }

    public function specific_feedback(question_attempt $qa) {
        $question = $qa->get_question();

        $answer = $question->get_matching_answer(array('answer' => $qa->get_last_qt_var('answer')));
        if (!$answer || !$answer->feedback) {
            return '';
        }

        return $question->format_text($answer->feedback, $answer->feedbackformat,
                $qa, 'question', 'answerfeedback', $answer->id);
    }

    public function correct_response(question_attempt $qa) {
        $question = $qa->get_question();

        $answer = $question->get_matching_answer($question->get_correct_response());
        if (!$answer) {
            return '';
        }

        // return get_string('correctansweris', 'qtype_shortanswer2',
        //         s($question->clean_response($answer->answer)));
        // return get_string('correctansweris', 'qtype_shortanswer2',
        //         s('\( ' . $answer->answer . ' \)'));

        // 没用$question->clean_response
        // format_text()第三四个参数不知到用处。。
        return $question->format_text(get_string('correctansweris', 'qtype_shortanswer2', s('\( ' . $answer->answer . ' \)')), $answer->feedbackformat,
                $qa, 'question', 'answerfeedback', $answer->id);
    }
}
