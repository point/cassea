
/**
 * Written by Rob Schmitt, The Web Developer's Blog
 * http://webdeveloper.beforeseven.com/
 */

/**
 * The following variables may be adjusted
 */

var active_color = '#000'; // Colour of user provided text
var inactive_color = '#777'; // Colour of default text

/**
 * No need to modify anything below this line
 */

$(document).ready(function() {
  $("input.default-value").css("color", inactive_color);
  var default_values = new Array();
  $("input.default-value").focus(function() {
    if (!default_values[this.id]) {
      default_values[this.id] = this.value;
    }
    if (this.value == default_values[this.id]) {
      this.value = '';
      this.style.color = active_color;
    }
	$(this).blur(function() {
	  if (this.value == '') {
		this.style.color = inactive_color;
		this.value = default_values[this.id];
	  }
	});
  });
});
/**
 * @author Dan Blaisdell
 */

/** using: $("#myElement").offset({left:34,top:100}); */

(function($){
	$.fn.extend({
		_offset : $.fn.offset,
		
		offset : function(newOffset){
		    return newOffset ? this.setXY(newOffset) : this._offset();
		},
		
		setXY: function(newOffset){
			return this.each(function(){
				var el = this;
				
				var hide = false;
				
				if($(el).css('display')=='none'){
					hide = true;
					$(el).show();
				}
				
				var style_pos = $(el).css('position');
				
				// default to relative
				if (style_pos == 'static') {
					$(el).css('position','relative');
					style_pos = 'relative';
				}
				
				var offset = $(el).offset();
				
				if (offset){
					var delta = {
						left : parseInt($(el).css('left'), 10),
						top: parseInt($(el).css('top'), 10)
					};
					
					// in case of 'auto'
					if (isNaN(delta.left)) 
						delta.left = (style_pos == 'relative') ? 0 : el.offsetLeft;
					if (isNaN(delta.top))
						delta.top = (style_pos == 'relative') ? 0 : el.offsetTop;
					
					if (newOffset.left || newOffset.left===0)
						$(el).css('left',newOffset.left - offset.left + delta.left + 'px');
				
					if (newOffset.top || newOffset.top===0)
						$(el).css('top',newOffset.top - offset.top + delta.top + 'px');
				}
				if(hide) $(el).hide();
			});
		}
	});
})(jQuery);

function debug(text) {
  ((window.console && console.log) ||
	 (window.opera && opera.postError) ||
		window.alert).call(this, text);
}
function show_error_boxes()
{

	function remove(el)
	{
		if(!el || !el.size()) return;
		//el.fadeTo('slow',0).remove();
		el.hide();
		el.remove();
		//show_error_boxes_int();
	}
	function show_error_boxes_int()
	{
		var firstdiv=$("span.w-error");
		if(!firstdiv.size()) return;
		firstdiv = firstdiv.eq(0);

		var message=firstdiv.text(); 
		var input=firstdiv.prevAll(" .wsnaperror, :input,:text,:checkbox label,:radio label,:file");
        if(!input.size()) return;
		input = input.eq(input.length - 1);
		firstdiv.remove();
		$("div.w_error_box").remove(); 
		$("<div class='w_error_box'><div class='wrapper'><img class='corner' src='/w_images/c.png'/>"+
			"<div class='ertop'>&nbsp;</div></div><div class='w_error_message'>"+message+"</div></div>").
			insertAfter(input)
		.offset({'top':input.offset().top+input.height()+5, 'left':input.offset().left}).width(Math.max(Math.min(input.width()+5,300),200)).css('opacity',1).show()/*.fadeTo('slow',0.8)*/
		.one('click',function(){ remove( $(this)); });
		input.one('click',function(){ remove($("~ .w_error_box",this)); });
		input.filter(".hasDatepicker").one('focus',function() { remove($("~ .w_error_box",this)); });
	}
	$("span.w-error").hide();
	//$("form").submit(function(){ $("div.w_error_box").remove(); });
	show_error_boxes_int();
}
$(document).ready(function(){ 
	show_error_boxes(); 
	$("[tabindex=1]:text").focus();
});
