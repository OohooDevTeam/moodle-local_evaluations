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

/**
 * javascript that runs on the teachers evaluation setup page. Removes the delete/up/down
 * buttons and locks the types of questions so we will be able to compare them later on 
 * without running into errors.
*/
var jNum = parseInt(document.getElementById('num_default_q').value);

if(jNum != -1){
    var dropdowns = document.getElementsByClassName('fselect');
    
    //Since there are selects before the questions. (Course and Email Reminders)
    //We must start later.
    var selB4Questions = 2;
    
    //Disable each standard question so they can't be changed. (This is just for show. No matter how they are changes they will
    //still stay the same as the standard questions when loaded into the database.
    for(var iNum = selB4Questions; iNum < jNum + selB4Questions; iNum++){
        var isstandard = document.getElementsByName('question_std[' + (iNum - selB4Questions) + ']')[0];

        if(isstandard.getAttribute('value') == 1) {
            var newText = document.createTextNode("This is a default question");
            var newSPAN = document.createElement("SPAN");
            newSPAN.appendChild(newText);
	    
            dropdowns[iNum].firstChild.disabled = true;
            dropdowns[iNum].appendChild(newSPAN);
        }
	   
    }
}

//Remove the up/down delete buttons.
var questionControl = document.getElementsByClassName('question_controls');

var leng = questionControl.length;

var todel = [];

for(var i = 0; i < leng; i++) {
    todel.push(questionControl[i]);
    
}

for(var i = 0; i < leng; i++){
    todel[i].parentNode.removeChild(todel[i]);
}
