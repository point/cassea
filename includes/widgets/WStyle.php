<?php
/*- vim:expandtab:shiftwidth=4:tabstop=4: 
{{{ LICENSE  
* Copyright (c) 2008, Cassea Project
* All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions are met:
*     * Redistributions of source code must retain the above copyright
*       notice, this list of conditions and the following disclaimer.
*     * Redistributions in binary form must reproduce the above copyright
*       notice, this list of conditions and the following disclaimer in the
*       documentation and/or other materials provided with the distribution.
*     * Neither the name of the Cassea Project nor the
*       names of its contributors may be used to endorse or promote products
*       derived from this software without specific prior written permission.
*
* THIS SOFTWARE IS PROVIDED BY CASSEA PROJECT ''AS IS'' AND ANY
* EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
* WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
* DISCLAIMED. IN NO EVENT SHALL CASSEA PROJECT BE LIABLE FOR ANY
* DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
* (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
* LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
* ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
* (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
* SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
}}} -*/


//
// $Id:$
//
//{{{ WStyle
class WStyle extends WObject
{
    private

        /**
        * @var      string
        */
		$accelerator = null, $azimuth = null, $background = null, $background_attachment = null, 
		$background_color = null, $background_image = null, $background_position = null, $background_position_x = null, 
		$background_position_y = null, $background_repeat = null, $behavior = null, $border = null, 
		$border_bottom = null, $border_bottom_color = null, $border_bottom_style = null, $border_bottom_width = null, 
		$border_collapse = null, $border_color = null, $border_left = null, $border_left_color = null, 
		$border_left_style = null, $border_left_width = null, $border_right = null, $border_right_color = null, 
		$border_right_style = null, $border_right_width = null, $border_spacing = null, $border_style = null, 
		$border_top = null, $border_top_color = null, $border_top_style = null, $border_top_width = null, 
		$border_width = null, $bottom = null, $caption_side = null, $clear = null, 
		$clip = null, $color = null, $content = null, $counter_increment = null, 
		$counter_reset = null, $cue = null, $cue_after = null, $cue_before = null, 
		$cursor = null, $direction = null, $display = null, $elevation = null, 
		$empty_cells = null, $filter = null, $float = null, $font = null, 
		$font_family = null, $font_size = null, $font_size_adjust = null, $font_stretch = null, 
		$font_style = null, $font_variant = null, $font_weight = null, $height = null, 
		$ime_mode = null, $include_source = null, $layer_background_color = null, $layer_background_image = null, 
		$layout_flow = null, $layout_grid = null, $layout_grid_char = null, $layout_grid_char_spacing = null, 
		$layout_grid_line = null, $layout_grid_mode = null, $layout_grid_type = null, $left = null, 
		$letter_spacing = null, $line_break = null, $line_height = null, $list_style = null, 
		$list_style_image = null, $list_style_position = null, $list_style_type = null, $margin = null, 
		$margin_bottom = null, $margin_left = null, $margin_right = null, $margin_top = null, 
		$marker_offset = null, $marks = null, $max_height = null, $max_width = null, 
		$min_height = null, $min_width = null, $_moz_binding = null, $_moz_border_radius = null, 
		$_moz_border_radius_topleft = null, $_moz_border_radius_topright = null, $_moz_border_radius_bottomright = null, $_moz_border_radius_bottomleft = null, 
		$_moz_border_top_colors = null, $_moz_border_right_colors = null, $_moz_border_bottom_colors = null, $_moz_border_left_colors = null, 
		$_moz_opacity = null, $_moz_outline = null, $_moz_outline_color = null, $_moz_outline_style = null, 
		$_moz_outline_width = null, $_moz_user_focus = null, $_moz_user_input = null, $_moz_user_modify = null, 
		$_moz_user_select = null, $orphans = null, $outline = null, $outline_color = null, 
		$outline_style = null, $outline_width = null, $overflow = null, $overflow_X = null, 
		$overflow_Y = null, $padding = null, $padding_bottom = null, $padding_left = null, 
		$padding_right = null, $padding_top = null, $page = null, $page_break_after = null, 
		$page_break_before = null, $page_break_inside = null, $pause = null, $pause_after = null, 
		$pause_before = null, $pitch = null, $pitch_range = null, $play_during = null, 
		$position = null, $quotes = null, $_replace = null, $richness = null, 
		$right = null, $ruby_align = null, $ruby_overhang = null, $ruby_position = null, 
		$_set_link_source = null, $size = null, $speak = null, $speak_header = null, 
		$speak_numeral = null, $speak_punctuation = null, $speech_rate = null, $stress = null, 
		$scrollbar_arrow_color = null, $scrollbar_base_color = null, $scrollbar_dark_shadow_color = null, $scrollbar_face_color = null, 
		$scrollbar_highlight_color = null, $scrollbar_shadow_color = null, $scrollbar_3d_light_color = null, $scrollbar_track_color = null, 
		$table_layout = null, $text_align = null, $text_align_last = null, $text_decoration = null, 
		$text_indent = null, $text_justify = null, $text_overflow = null, $text_shadow = null, 
		$text_transform = null, $text_autospace = null, $text_kashida_space = null, $text_underline_position = null, 
		$top = null, $unicode_bidi = null, $_use_link_source = null, $vertical_align = null, 
		$visibility = null, $voice_family = null, $volume = null, $white_space = null, 
		$widows = null, $width = null, $word_break = null, $word_spacing = null, 
		$word_wrap = null, $writing_mode = null, $z_index = null, $zoom = null,
        /**
        * @var      boolean
        */
		$attr_setted = 0
		;
    // {{{ __construct
    /**
    * Method description
    *
    * More detailed method description
    * @param    array $params
    */
    function __construct($id = null)
    {
		parent::__construct($id);
    }
    // }}}
	
     // {{{ parseParams 
    /**
    * Method description
    *
    * More detailed method description
    * @param    array $params
    * @return void
    */
    function parseParams($params)
	{
		if(isset($params))
			foreach($params->attributes() as $k => $v)
				if(strpos("-",$k) !== false)
					$this->setAttribute(str_replace("-","_",$k),$v);
				else $this->setAttribute($k,$v);
    }
    // }}}

    // {{{ generateStyle 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function generateStyle()
    {
		if(!$this->attr_setted) return "";
		$final_style = "";
		$vars = get_class_vars("WStyle");
		foreach($vars as $k => $v)
		{
			if(isset($this->$k) && is_string($this->$k) && $k !== "attr_setted" && $k !== "id")
			{
				$k2 = str_replace("_","-",$k);
				$final_style.= $k2.":".$this->$k.";";
			}
		}
		return $final_style;
    }
     // }}}
    
    // {{{ setAttribute 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $attribute    
    * @param    mixed $value    
    * @return   void
    */
    function setAttribute($attribute, $value)
    {
		//$attribute = str_replace("-","_",$attribute);
		$vars = get_class_vars("WStyle");
		$setted = 0;	
		foreach ($vars as $k=>$v)
		{	
			if($attribute == $k)
			{
				$this->$attribute = "".$value;
				$this->attr_setted = 1;
			}
		}
    }
    // }}}
	
    // {{{ setDefault
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $attribute    
    * @param    mixed $value    
    * @return   void
    */
    function setDefault($attribute, $value)
    {
		if(isset($this->$attribute)) return;
		$this->setAttribute($attribute,$value);
    }
    // }}}
   
    // {{{ getAttribute
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $attribute    
    * @return   mixed
    */
    function getAttribute($attribute)
    {
		//$attribute = str_replace("-","_",$attribute);
		$vars = get_class_vars("WStyle");
		foreach ($vars as $k=>$v)
			if($attribute == $k)
				return $this->$attribute;
    }
    // }}}

    // {{{ isEmpty
    /**
    * Method description
    *
    * More detailed method description
    * @return   boolean
    */
    function isEmpty()
    {
		return !$this->attr_setted;
    }
    // }}}

}
//}}}

?>
