/**
 * Plugin Melanie2 Applications
 *
 * plugin melanie2 pour lister et configurer les app de roundcube
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

if (window.rcmail) {
  rcmail.addEventListener('init', function(evt) {
	  	$("#taskbar a.button-melanie2_applications").insertAfter($("#taskbar a.button-logout"));
	  	$("#taskbar a.button-melanie2_applications").append("<div class=\"arrow-up\" />");
	  	$("#taskbar a").each(function() {
	  		var item = $(this);
	  		var app_name = item.attr('class').split(' ')[0]
	  		if (rcmail.env.applications_ignore.indexOf(app_name) == -1) {
	  			item.addClass("draggable_taskbar");	  			
	  			if (rcmail.env.applications_list.indexOf(app_name) == -1) {
	  				$("#taskbar #applications_list_m2 div ul").append("<li class=\""+app_name+" droppable\" style=\"display: none;\">" + item.clone().removeClass("draggable_taskbar").addClass("draggable").wrap('<p>').parent().html() + "</li>")					
	  			}
	  		}
		});
	  	for (var key in rcmail.env.applications_list) {
	  		var app_name = rcmail.env.applications_list[key];
	  		var item = $("#taskbar a." + rcmail.env.applications_list[key]);
	  		if (item.length) {
	  			$("#taskbar #applications_list_m2 div ul").append("<li class=\""+app_name+" droppable\">" + item.clone().removeClass("draggable_taskbar").addClass("draggable").wrap('<p>').parent().html() + "</li>")
				item.hide();
	  		}
	  	}
	  	if (rcmail.task != 'jappix') {
			$("#taskbar a.draggable_taskbar").draggable({
				revert : true,
				containment : '#taskbar',
				axis : 'x',
				start: function(event, ui) {
			        $(this).attr("onclick", "");
			        $(".button-melanie2_applications div.arrow-up").show();
			        $("#taskbar #applications_list_m2_tips").show();
			        $("#taskbar #applications_list_m2").hide();
			        $("#taskbar .button-melanie2_applications").removeClass("button-selected");
			    },
			    stop: function(event, ui) {
			    	$("#taskbar #applications_list_m2_tips").hide();
			    	if (!$("#taskbar #applications_list_m2").is(":visible") )
			    		$(".button-melanie2_applications div.arrow-up").hide();
			    },
			});
			$("#taskbar a.button-melanie2_applications").droppable({
				drop: function(event, ui) {
					// The droppable (li element).
			        var droppable = $(this);
			        			        
			        // The draggable (span element).
			        var draggable = ui.draggable;
			        
			        if (droppable.hasClass("button-melanie2_applications")) {
			        	var app_name = draggable.attr('class').split(' ')[0]
			        	// Remove the position of the dragged draggable, because there's still some css left of the dragging.
			            draggable.css({"top": 0, "left": 0});
			            $("#taskbar #applications_list_m2_tips").hide();
				        $("#taskbar #applications_list_m2").show();
				        $("#taskbar .button-melanie2_applications").addClass("button-selected");
				        $(".button-melanie2_applications div.arrow-up").show();
				        $("#taskbar #applications_list_m2 div ul li." + app_name).show();
			        	draggable.hide();
			        	rcmail.modify_applications_order();
			        }
				}
			});
			$("#taskbar .app_list .draggable").draggable({ 
				revert : true,
				revertDuration: 0,
				start: function(event, ui) {
			        $(this).attr("onclick", "");
			        $("#taskbar #applications_list_m2  .drop_add_toolbar").show();
			    },
			    stop: function(event, ui) {
			    	$("#taskbar #applications_list_m2  .drop_add_toolbar").hide();
			    },
			});
			$("#taskbar .app_list .droppable").droppable({
				activeClass: "active",
				hoverClass: "hover",
			    accept: function (draggable) {
			        // The droppable (li element).
			        var droppable = $(this);
			        // If its droppable zone
			        if (droppable.hasClass("drop_add_toolbar"))
			        	return true;
			        // The droppable which contains the draggable, i.e., the parent element of the draggable (li element).
			        var draggablesDropable = draggable.parent();
			        // Is the draggable being dragged/sorted to the same group?
			        // => We could just sort it, because there's always enough space inside the group.
			        if (droppable.parent().is(draggablesDropable.parent())) {
			           return true;
			        }
			        // Nope, the draggable is being dragged/sorted to another group.
			        // => Is there an empty droppable left in the group to which the draggable is being dragged/sorted?
			        else if (droppable.parent().find(".draggable").size() < droppable.parent().find(".droppable").size()) {
			            return true;
			        }
			        
			        // Nothing true?
			        return false;
			    },
			    drop: function(event, ui) {
			        // The droppable (li element).
			        var droppable = $(this);
			        			        
			        
			        // The draggable (span element).
			        var draggable = ui.draggable;
			        
			        // The droppable which contains the draggable, i.e., the parent element of the draggable (li element).
			        var draggablesDropable = draggable.parent();
			        
			        // If its droppable zone
			        if (droppable.hasClass("drop_add_toolbar")) {
			        	var app_name = draggable.attr('class').split(' ')[0]
			        	$("#taskbar a."+app_name).show();
			        	draggablesDropable.hide();
			        	$("#taskbar #applications_list_m2 .drop_add_toolbar").hide();
			        	rcmail.modify_applications_order();
			        }
			        // Is the draggable being dragged to it's own droppable?
			        // => Abort, there's nothing to drag/sort!
			        else if (droppable.is(draggablesDropable)) {
			            return;
			        }		        
			        // Is the draggable being dragged/sorted to the same group?
			        // => We can just sort it, because there's always enough space inside the group.
			        else if (droppable.parent().is(draggablesDropable.parent())) {
			            // Is the draggable being dragged up?
			            if (droppable.parent().find(".droppable").index(draggablesDropable) > droppable.parent().find(".droppable").index(droppable)) {
			                // Add the dragged draggable's droppable before the droppable.
			                draggablesDropable.insertBefore(droppable);
			            }
			            
			            // No, the draggable is being dragged down.
			            else {
			                // Add the dragged draggable's droppable after the droppable.
			                draggablesDropable.insertAfter(droppable);
			            }
			            rcmail.modify_applications_order();
			        }			        
			        // Nope, the draggable is being dragged/sorted to another group.
			        // => Is there an empty droppable left in the group to which the draggable is being dragged/sorted?
			        else if (droppable.parent().find(".draggable").size() < droppable.parent().find(".droppable").size()) {
			            // Find the first empty droppable in which the draggable is being dragged/sorted.
			            var emptyDroppable = $($.grep(droppable.parent().find(".droppable"), function (item) {
			                // Are there draggables inside this droppable?
			                // => Return TRUE if not.
			                return !$(item).find(".draggable").size();
			            })).first();		            
			            // Clone the dragged draggable's droppable before itself, because we need to remember it's position after moving it.
			            var draggablesDropableClone = draggablesDropable.clone().insertBefore(draggablesDropable);
			            
			            // Is the draggable being dragged above the empty droppable?
			            if (droppable.parent().find(".droppable").index(emptyDroppable) > droppable.parent().find(".droppable").index(droppable)) {
			                // Add the dragged draggable's droppable before the droppable.
			                draggablesDropable.insertBefore(droppable);
			            }
			            // No, the draggable is being dragged below the empty droppable.
			            else {
			                // Add the dragged draggable's droppable after the droppable.
			                draggablesDropable.insertAfter(droppable);
			            }
			            // Remove the position of the dragged draggable, because there's still some css left of the dragging.
			            draggable.css({"top": 0, "left": 0});			            
			            // Add the first empty droppable before the cloned draggable's droppable. Remove the latter afterwards.
			            draggablesDropableClone.before(emptyDroppable).remove();
			            rcmail.modify_applications_order();
			        }			        
			    }
			});
	  	}
		$('html').click(function() {					
			if ($("#taskbar #applications_list_m2").is(":visible")) {
				$("#taskbar #applications_list_m2").hide();
				$(".button-melanie2_applications div.arrow-up").hide();
				$("#taskbar .button-melanie2_applications").removeClass("button-selected");
			}
		});
		$("#taskbar .button-melanie2_applications").click(function(e) {
			e.stopPropagation();
			if ($("#taskbar #applications_list_m2").is(":visible")) {
				$("#taskbar #applications_list_m2").hide();
				$(".button-melanie2_applications div.arrow-up").hide();
				$("#taskbar .button-melanie2_applications").removeClass("button-selected");
			} else {
				$("#taskbar #applications_list_m2").show();
				$(".button-melanie2_applications div.arrow-up").show();
				$("#taskbar .button-melanie2_applications").addClass("button-selected");
			}
		});
  });
}

rcube_webmail.prototype.modify_applications_order = function()
{
	var applications_list = [];
	$("#taskbar #applications_list_m2 li a:visible").each(function() {
		applications_list.push($(this).attr('class').split(' ')[0]);
	});
	if (applications_list) {
		this.http_post('applications/modify_applications_order', {_applications_list: applications_list});
	}
};



