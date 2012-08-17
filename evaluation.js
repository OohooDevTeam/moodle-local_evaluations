/**
 * ************************************************************************
 * *                              Evaluation                             **
 * ************************************************************************
 * @package     local                                                    **
 * @subpackage  Evaluation                                               **
 * @name        Evaluation                                               **
 * @copyright   oohoo.biz                                                **
 * @link        http://oohoo.biz                                         **
 * @author      Dustin Durrand           				 **
 * @author      (Modified By) James Ward   				 **
 * @author      (Modified By) Andrew McCann				 **
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later **
 * ************************************************************************
 * ********************************************************************** */

////javascript that runs on the teachers evaluation setup page. Removes the delete/up/down
//buttons and locks the types of questions so we will be able to compare them later on 
//without running into errors.

var jNum = parseInt(document.getElementById('num_default_q').value);

if(jNum != -1){
    var dropdowns = document.getElementsByClassName('fselect');
    for(var iNum = 3; iNum < jNum+3; iNum++){
        var isstandard = document.getElementsByName('question_std[' + (iNum - 3) + ']')[0];
        
        if(isstandard.getAttribute('value') == 1) {
            var newText = document.createTextNode("This is a default question");
            var newSPAN = document.createElement("SPAN");
            newSPAN.appendChild(newText);
	    
            dropdowns[iNum].firstChild.disabled = true;
            dropdowns[iNum].appendChild(newSPAN);
        }
	   
    }
}

var questionControl = document.getElementsByClassName('question_controls');

var leng = questionControl.length;

var todel = [];

for(var i = 0; i < leng; i++) {
    todel.push(questionControl[i]);
    
}

for(var i = 0; i < leng; i++){
    todel[i].parentNode.removeChild(todel[i]);
}
