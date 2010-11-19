<?php

	// fonts
	$font['body']  = 'Verdana, Helvetica, sans-serif';
	$font['footer']  = 'Verdana, Helvetica, sans-serif';
	$font['title']  = 'Verdana, Helvetica, sans-serif';
	$font['label'] = 'Verdana, Helvetica, sans-serif';
	$font['serif'] = "Georgia, 'Times New Roman', serif";
	$font['sans_serif'] = 'Verdana, Helvetica, sans-serif';
	
	// font-sizes: consider changing to relative sizes: xx-small | x-small | small | medium | large | x-large | xx-large
	// fonts were changed to px instead of pt to avoid the bad display on misconfigured laptops.
	$fontSize['h1']         = '14pt';
	$fontSize['h2']         = '13pt';
	$fontSize['h3']         = '12pt';
	$fontSize['h4']         = '12pt';
	$fontSize['h5']         = '12pt';
	
	$fontSize['body']       = '10pt';
	$fontSize['td']         = '10pt';
	$fontSize['errors']     = '10pt';
	$fontSize['nav']        = '10pt';
	$fontSize['footer']     = '10pt';
	$fontSize['label']      = '8pt';
	$fontSize['toplink']    = '8pt';
	$fontSize['details']	= '7pt';

//	foreach ($fontSize as $key => $value) {
//		$fontSize[$key] = HTML::pt2px($value);
//	}
	
	// colors
	$color['notice']          = '#D00'; // red
	$color['error']           = '#D00'; // red
	$color['info']            = '#0D0'; // green
	$color['nav_bg']          = '#020A45'; // light gray
	$color['subnav_bg']       = '#DDF'; // light blue
	$color['body']            = '#FFF'; // white
	$color['body_text']       = '#000'; // black
	$color['nav_fg']          = '#5B7BA1'; // dark blue-gray
	$color['nav_hv']          = '#444'; // light blue-gray
	$color['pk_bg']           = '#F6F6F6'; // light blue-gray
	$color['ir']              = '#DEF'; // light blue-gray
	$color['faq']             = '#009'; // dark blue
	$color['delicate']   	  = '#EEE'; // light gray
	$color['shadow']   	  	  = '#777'; // gray
	$color['page']            = '#FFF'; // white
	$color['back']            = '#D9DCE1'; // light blue gray
	$color['page_border']     = '#BBB'; // med gray
	$color['reversed_text']   = '#FFF'; // white
	$color['footer']          = '#525252'; // dark gray
	$color['pdf']             = '#333'; // gray
	$color['label']           = '#777'; // gray
	$color['headerbg']        = '#FFF'; // white
	$color['background']      = '#FFF'; // white
	$color['link']            = '#83BAD4'; // dark blue-gray


	// spacing
	$space['base']           = '20px';
	$space['narrow']         = intval ($space['base']/2).'px';
	$space['thin']           = intval ($space['base']/4).'px';
	$space['wide']           = intval ($space['base']*2).'px';
	$space['slice']          = '1px';
	$space['border']         = '1px';
	$space['page_margin']    = '100px';
	$space['step_margin']    = '200px';
	if (HTML::isMobile()) {
		$space['page']			= '476px';
		$space['layout_width'] 	= '436px';
		$space['block_width'] 	= '436px';
		$space['sidebar']		= '140px';		
	} else {
		$space['page']			= '836px';
		$space['layout_width'] 	= '836px';
		$space['block_width'] 	= '836px';
		$space['sidebar']		= '236px';
		$space['page_sidebar']	= '600px';
	}
	$space['callout']		= '150px';
	$space['bigcallout']	= '250px';
	$space['img_pad']		= '5px';
	$space['block_pad']		= '10px';
	
	// echo $css_browser['name'].' '.$css_browser['version'];
	
	$spacing = $space['base']*2;
	$width = $space['page'] - $spacing;
	$feature = $width-$space['callout']-$space['narrow']*3;
	$space['tag'] = $width.'px';
	
	$space['content']        = $width.'px';
	$space['feature']        = $feature.'px';

	$css_watermark = '';
	if (file_exists('../images/sitewm.gif')) {
		$css_watermark = 'background-image: url(../images/sitewm.gif); background-repeat: repeat;';
	}
	
?>