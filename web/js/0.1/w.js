function popup(url, title,  width, height,relative_to_id){
		if(!url) return null;
		if(!title) title = url;
		if(!width) width = 800;
		if(!height) height = 600;
		var str = "";
		if(relative_to_id && $("#"+relative_to_id))
		{
			var x = $("#"+relative_to_id).offset().left;
			var y = $("#"+relative_to_id).offset().top;

			if($.browser.opera)
			{	x +=  window.screenLeft;y +=  window.screenTop;	}
			if($.browser.mozilla)
			{	x += window.screenX;y += window.screenY;}
			if($.browser.msie)
			{x += (window.screenLeft - window.parent.document.body.scrollLeft);	y += (window.screenTop - window.parent.document.body.scrollTop);}
			if(x + width > (screen.width-40))
				x = screen.width-width-40;
			if(y + height > (screen.height-40))
				y = screen.height - height - 40;
			str = ", screenX="+x+", left="+x+", screenY="+y+", top="+y;
		}
		newwin=window.open(url, '',"width="+width+", height="+height+",toolbar=no,location=no, menubar=no, titlebar=no, dependent, scrollbars=0,status=0,resizeable=0"+(str?str:""));
		newwin.document.title = title;
		return newwin;
}
function mselect_exclude(obj)
{
	if(!obj) return;
	if(!obj.options) return;
	l = obj.options.length;
	for(i = l-1; i >= 0; i--)
		if(obj.options[i].selected)
			obj.options[i] = null;
}
function crosslinksAddOneSide(from_obj,to_obj)
{
	if(!from_obj  || !to_obj) return;
	if(from_obj.selectedIndex == -1) return;
	for(i = 0; i < from_obj.options.length;i++)
	{
		if(!from_obj.options[i].selected) continue;
		o_text = from_obj.options[i].text;
		o_text = " ->"+o_text;
		o_value_raw = from_obj.options[i].value;
		o_value = from_obj.options[i].value+"_1";
		newopt = new Option(o_text,o_value);
		var flag = 0;
		for(j = 0; j < to_obj.length;j++)
			if(to_obj[j].value == o_value || to_obj[j].value == o_value_raw+"_2") flag = 1;
		if(!flag)
			to_obj.options[to_obj.options.length] = newopt;
	}
	from_obj.selectedIndex = -1;
}
function crosslinksAddTwoSide(from_obj,to_obj)
{
	if(!from_obj || !to_obj) return;
	if(from_obj.selectedIndex == -1) return;
	for(i = 0; i < from_obj.options.length;i++)
	{
		if(!from_obj.options[i].selected) continue;
		o_text = from_obj.options[i].text;
		o_text = "<->"+o_text;
		o_value_raw = from_obj.options[i].value;
		o_value = from_obj.options[i].value+"_2";
		newopt = new Option(o_text,o_value);
		var flag = 0;
		for(j = 0; j < to_obj.length;j++)
			if(to_obj[j].value == o_value || to_obj[j].value == o_value_raw+"_1") flag = 1;
		if(!flag)
			to_obj.options[to_obj.options.length] = newopt;
	}
	from_obj.selectedIndex = -1;
}
function make_hidden(obj) 
{
	if(!obj) return;
	len = obj.length;
	if (len==0)	obj.options[0] = new Option("","");
	len = obj.length;
	for(i = 0; i < len;i++)	obj.options[i].selected = true;
}
function displayEndRequest()
{ $("#ajax_helper").remove();}
function displayBeginRequest()
{
	var im = new Image();
	im.src = "/way_admin/images/widgets/ajax/way_loader_50x50.gif";
	if(document.getElementById("ajax_helper")) return;
	var v_size = [];
	v_size[0] = window.document.body.offsetWidth;
	v_size[1] = window.document.body.offsetHeight;
	var scroll_pos = getScrollingPosition();
	$('<div id="ajax_helper" class="ajax_helper"><img src="/way_admin/images/widgets/ajax/way_loader_50x50.gif"/></div>').prependTo("#marker").hide();
	var d_h = parseInt($("#ajax_helper").css('height'));
	var d_w = parseInt($("#ajax_helper").css('width'));
	var x = Math.round(v_size[0]/2 - d_w/2 + scroll_pos[0]);
	var y = Math.round(v_size[1]/2 - (d_h/2) + scroll_pos[1]);
	$("#ajax_helper").css('left',x).css('top',y).fadeIn('fast');
}
function getScrollingPosition() 
{ 
 var position = [0, 0]; 
 var win = window;
 while(win != window.parent)
	win = window.parent;
 if (typeof win.pageYOffset != 'undefined') 
   position = [win.pageXOffset,win.pageYOffset]; 
 else if (typeof win.document.documentElement.scrollTop 
     != 'undefined' && win.document.documentElement.scrollTop > 0) 
   position = [ win.document.documentElement.scrollLeft, win.document.documentElement.scrollTop ]; 
 else if (typeof win.document.body.scrollTop != 'undefined') 
   position = [ win.document.body.scrollLeft, win.document.body.scrollTop ]; 
 return position; 
}
function mselectMakeFirst(obj) 
{
	if(!obj || !obj.length) return;
	 len = obj.length;
	j = 0;
	for(i = 0; i < len; i++)
		if(obj.options[i].selected)
		{
			for(k = i; k > j; k--)
				swapOptions(obj,k,k-1);
			j++;
		}
}
function swapOptions(obj,i,j) 
{
	if(!obj || !obj.length) return;
	text = obj.options[i].text;
	value = obj.options[i].value;
	obj.options[i].text = obj.options[j].text;
	obj.options[i].value = obj.options[j].value;
	obj.options[j].text = text;
	obj.options[j].value = value;
	obj.options[j].selected = true;
	obj.options[i].selected = false;
}
function mselectMoveOptionUp(obj) 
{
	if(!obj) return;
	len = obj.length;
	for(i = 1; i < len; i++)
		if(obj.options[i].selected)
			swapOptions(obj,i,i-1);
}
function mselectMoveOptionDown(obj) 
{
	if(!obj) return;
	len = obj.length;
		for(i = len-2; i >= 0;i--)
			if(obj.options[i].selected)
				swapOptions(obj,i,i+1);
}
function mselectMakeLast(obj) 
{
	if(!obj) return;
	 len = obj.length;
	j = 1;
	for(i = len-1; i >= 0; i--)
		if(obj.options[i].selected)
		{
			for(k = i; k < len-j; k++)
				swapOptions(obj,k,k+1);
			j++;
		}
}
function mselectAdd(from_obj,to_obj)
{
	if(from_obj == null) return;
	if(to_obj == null) return;
	if(from_obj.selectedIndex == -1) return;
	for(i = 0; i < from_obj.options.length;i++)
	{
		if(!from_obj.options[i].selected) continue;
		o_text = from_obj.options[i].text;
		o_value = from_obj.options[i].value;
		newopt = new Option(o_text,o_value);
		newopt.text = o_text;
		newopt.value=o_value;
		var flag = 0;
		for(j = 0; j < to_obj.length;j++)
			if(to_obj[j].value == o_value ) flag = 1;
		if(!flag)
			to_obj.options[to_obj.options.length] = newopt;
	}
	from_obj.selectedIndex = -1;
}
function spinnerUp(obj,step,max)
{
	if(!obj) return;
	if(obj.disabled) return;
	v = obj.value;
	if(v == "") v = 0;
	else v = parseInt(v);
	if(v + step > max) return;
	else obj.value = v + step;
}
function spinnerDown(obj,step,min)
{
	if(!obj) return;
	if(obj.disabled) return;
	v = obj.value;
	if(v == "") v = 0;
	else v = parseInt(v);
	if(v - step < min) return;
	else obj.value = v - step;
}
function isDigit(e)
{
	if(e.keyCode == 8 || e.keyCode == 45 || (48 <= e.keyCode && e.keyCode <= 57))
		return true;	
	else return false;
}

