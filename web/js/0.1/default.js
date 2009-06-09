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
		show_error_boxes_int();
	}
	function show_error_boxes_int()
	{
		var firstdiv=$("div.w-error");
		if(!firstdiv.size()) return;
		firstdiv = firstdiv.eq(0);

		var message=firstdiv.html(); 
		var input=firstdiv.prev(":input,:text,:checkbox label,:radio label,:file");
		firstdiv.remove();
		$("<div class='w-error w-error-box'><div class='wrapper'><img class='corner' src='/w_images/c.png'/>"+
			"<div class='ertop'>&nbsp;</div></div><div class='w-error-message'>"+message+"</div></div>").insertAfter(input)
		.css('top',input.offset().top+input.height()+5).css('left',input.offset().left).width(Math.max(input.width()+5,150)).css('opacity',1).show()/*.fadeTo('slow',0.8)*/
		.one('click',function(){ remove( $(this)); });
		input.one('focus',function(){ remove($("+ .w-error-box",this)); })
		;
	}
	$("div.w-error").hide();
	show_error_boxes_int();
}
$(document).ready(function(){ show_error_boxes(); 
$("[tabindex=1]:text").focus();
});
