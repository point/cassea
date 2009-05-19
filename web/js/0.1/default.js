function show_error_boxes()
{

	function remove(el)
	{
		if(!el || !el.size()) return;
		el.fadeTo('slow',0).remove();
		show_error_boxes_int();
	}
	function show_error_boxes_int()
	{
		var firstdiv=$("div.w-error");
		if(!firstdiv.size()) return;
		firstdiv = firstdiv.eq(0);

		var message=firstdiv.html(); 
		var input=firstdiv.prevAll(":input,:text,:checkbox label,:radio label,:file");
		firstdiv.remove();
		$("<div class='w-error-box'><div class='wrapper'><img class='corner' src='/w_images/c.png'/>"+
			"<div class='ertop'>&nbsp;</div></div><div class='w-error'>"+message+"</div></div>").insertAfter(input)
		.css('top',input.offset().top+input.height()+5).css('left',input.offset().left).width(Math.max(input.width()+5,150)).show().fadeTo('slow',0.8)
		.one('click',function(){ remove( $(this)); });
		input.one('focus',function(){ remove($(".w-error-box").eq(0)); })
		;
	}
	$("div.w-error").hide();
	show_error_boxes_int();
}
$(document).ready(function(){ show_error_boxes(); });
