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
 * JavaScript required by the multianswer2 question type.
 *
 * @package    qtype
 * @subpackage multianswer2
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


M.qtype_multianswer2 = M.qtype_multianswer2 || {};


M.qtype_multianswer2.init = function (Y, questiondiv) {
    Y.one(questiondiv).all('span.subquestion').each(function(subqspan) {
        var feedbackspan = subqspan.one('.feedbackspan');
        if (!feedbackspan) {
            return;
        }

        var overlay = new Y.Overlay({
            srcNode: feedbackspan,
            visible: false,
            align: {
                node: subqspan,
                points: [Y.WidgetPositionAlign.TC, Y.WidgetPositionAlign.BC]
            },
            constrain: subqspan.ancestor('div.que'),
            zIndex: 1,
            preventOverlap: true
        });
        overlay.render();

        Y.on('mouseover', function() { overlay.show(); }, subqspan);
        Y.on('mouseout', function() { overlay.hide(); }, subqspan);

        feedbackspan.removeClass('accesshide');

        
    });

    // Y.use('mathjax', function(){
    //     Y.all('.filter_mathjaxloader_equation').each(function(feedbackspan){
    //         MathJax.Hub.Queue(["Typeset", MathJax.Hub, feedbackspan.getDOMNode()]);
    //     })
    // })

    function contains(arr, obj) {
        var i = arr.length;
        while(i--){
            if(arr[i]===obj){
                return true;
            }
        }
        return false;
    }
    var formobj = Y.one('#responseform');
    if(formobj){
        formobj.on('submit', function() {
            Y.all('.multianswer2').each(function(q){
                var answerlist = [];
                q.all('span.subquestion').each(function(subqspan) {
                    var answer =  subqspan.one('input[name$=answer]').get('value');
                    if(contains(answerlist, answer)) {
                        subqspan.one('input[name$=duplicate]').set('value', '1');
                    }
                    answerlist.push(answer);
                })
            })
        });
    }
};
