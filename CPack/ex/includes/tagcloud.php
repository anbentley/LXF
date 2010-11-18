<?php

/**
 * TAGCLOUD creates a div block of words that satify the specified frequency criteria
 *
 * @author	Alex Bentley
 * @history	1.0	initial release
 */
class TAGCLOUD {

/**
 * Defines the default values for tagcloud creation.
 *
 * @details	Default values
 *	height;				0;		the height of the desired block. (0 means undefined).
 *	width;				400;	the desired width of the block. (0 means undefined).
 *	min-count;			1;		the minimum frequency count to display.
 *	max-count;			-1;		where to stop if looking for small limits.
 *	min-font-size;		12;		the smallest font size.
 *	max-font-size;		30;		the largest font size to use.
 *	maximum-results;	0;		the most entries to return (0 means no limit).
 *	ignore-words;		...;	a full collection of words to skip (html terms, prepositions, people references, common words, numbers...
 *	url-page;			this;	what page to go to if word is clicked.
 *	url-params;			;		a list of parameters to add to the existing url.
 */
function defaults () {
	$ignore = array_merge(
		DOCSEARCH::stopwords(), 
		
		str_split('abcdefghijklmnopqrstuvwxyz', 1), 
		
		// special words to not include
		array(
			// html tags and terms
			'align', 'br', 'class', 'color', 'com', 'div', 'em', 'face', 'font', 'href', 'html', 'http', 'https', 'img', 'input', 'left', 'li', 'margin', 
			'option', 'org', 'pt', 'px', 'right', 'select', 'size', 'span', 'st', 'style', 'table', 'title', 'www', 
			'arial', 'helvetica', 'roman', 'times', 
			
			// html entities
			'amp', 'ldquo', 'lsquo', 'mdash', 'nbsp', 'quot', 'rdquo', 'rsquo', 
			
			// MS stuff
			'bodyparagraph', 'countryregion', 'msonormal', 
			
			// common words
			'all', 'also', 'can', 'could', 'do', 'had', 'has', 'have', 'may', 'more', 'most', 'much', 'other', 'should', 'so', 'some', 'than', 'the', 'would', 
			'how', 'what', 'when', 'where', 'which', 'why', 
			
			// people references
			'are', 'any', 'he', 'her', 'hers', 'his', 'it', 'its', 'my', 'others', 'our', 'she', 'their', 'theirs', 'them', 'they', 'was', 'we', 'were', 'who', 'you', 
			
			// numbers
			'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten',
			
			// prepositions
			'above', 'about', 'across', 'after', 'against', 'along', 'amid', 'amoung', 'around', 'at', 'atop',
			'before', 'behind', 'below', 'beneath', 'beside', 'between', 'beyond', 'but', 'by',
			'concerning', 'despite', 'down', 'during', 'except', 'for', 'from', 'in', 'inside', 'into', 
			'like', 'near', 'of', 'off', 'on', 'onto', 'out', 'outside', 'over', 'past', 'regarding', 'since', 
			'through', 'throughout', 'till', 'to', 'toward', 'under', 'underneath', 'until', 'up', 'upon', 'with', 'within', 'without',
		)
	);
	
	return array(
		'height' => 0,
		'width' => 400,
		'min-count' => 1,
		'max-count' => -1,
		'min-font-size' => 12,
		'max-font-size' => 30,
		'maximum-results' => 0,
		'ignore-words' => $ignore,
		'url-page' => PARAM::page(), 
		'url-params' => array(),
	);
}

/**
 * Analyzes and generates a tagcloud based on the passed data and criteria.
 *
 * @param	$data	a string of text to evaluate.
 * @param	$options	an array of values that can override defaults.
 * @return	an HTML text string containing the tagcloud.
 * @see	defaults
 */
function display ($data, $options=array()) {
	$options = UTIL::merge(self::defaults(), $options);
	
	$data = str_replace(str_split('!@#$%^&*()_-+={}[]|:;"'."'".'<>,.?`~', 1), ' ', strtolower($data));
	
	$frequency = array();
	$tagwordcloud = '';
	if (array_key_exists('tag-words', $options)) {
		$tagwords = $options['tag-words'];
		
		foreach ($tagwords as $pattern) {
			$frequency[$pattern] = preg_match_all("/$pattern/" , $data, $matches);
		}
		$tagwordcloud = self::buildCloud($frequency, $options);
	}
	
	$frequency = array_count_values(str_word_count($data, 1));
	$tagcloud = self::buildCloud($frequency, $options);

	return $tagwordcloud.'<br />'.$tagcloud;
}
	
function buildCloud($frequency, $options) {
	foreach ($options['ignore-words'] as $ignore) if (array_key_exists($ignore, $frequency)) unset($frequency[$ignore]);
		
	// limit results to maximum-results
	if (($options['maximum-results'] > 0) && (count($frequency) > $options['maximum-results'])) {
		if ($options['max-count'] > 0) { // get least frequent
			asort($frequency);
			
		} else if ($options['min-count'] > 0) { // get most frequent
			arsort($frequency);
		}
		$frequency = array_slice($frequency, 0, $options['maximum-results']-1, true);
	}
	
	// remove items outside specified limits
	if ($options['min-count'] > 0) {
		foreach ($frequency as $tag => $count) {
			if ($count < $options['min-count']) unset($frequency[$tag]);
		}
	}
	
	if ($options['max-count'] > 0) {
		foreach ($frequency as $tag => $count) {
			if ($count > $options['max-count']) unset($frequency[$tag]);
		}
	}

	// don't mess up original array
	$freqcount = $frequency;
	arsort($freqcount);
	$minimumCount =	array_pop($freqcount);
	$maximumCount = array_shift($freqcount);
	
	$spread = $maximumCount - $minimumCount;
	$result = '';

	$spread == 0 && $spread = 1; // make sure spread is never 0
	
	ksort($frequency);
	
	$color = '';
	if ($options['page'] == '') $color = ' color: black;';
	
	foreach ($frequency as $tag => $count) {
		$size = floor($options['min-font-size'] + ($count - $minimumCount)/$spread * ($options['max-font-size'] - $options['min-font-size']));
	
		$attr = array('style' => "font-size: {$size}px;$color", 'class' => 'tag_cloud', 'title' => "$tag appeared $count times");
		if ($options['page'] != '') {
			$urlparams = array_merge($options['url-params'], array('all' => PARAM::value('all').' '.$tag));
			
			$page = LINK::paramTag($options['url-page'], $urlparams, htmlspecialchars(stripslashes($tag)), UTIL::merge($attr, LINK::rtn()));
			
		} else {
			$page = HTML::tag('span', $attr, $tag);
		}
		UTIL::append($result, $page, ' ');
	}
	
	$width = '';
	if ($options['width'] > 1) $width = "width: $options[width]px; ";
	$height = '';
	if ($options['height'] > 1) $height = "height: $options[height]px; ";
	
	return HTML::tag('div', array('class' => 'tagcloud', 'style' => "{$width}{$height}border: 1px solid black; padding: 1em; margin: 1em; background-color: #DDD;"), $result);
}

}