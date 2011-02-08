<?php

/**
 * FORM encapsulates all form building functions.
 * 
 * All functions are intended to be used statically.
 *
 * The most commonly used method is complete - presents and validates an HTML form
 *
 * most other methods are called internally based on a form definition
 *
 * The following is a simple form definition:
 *
 *	$field[] = array('element' => 'text', 'name' => 'subject', 'value' => 'Feedback', 'hidden'); 
 *	$field[] = array('element' => 'text', 'name' => 'to', 'label' => 'To', 'value' => 'Feedback'); 
 *	$field[] = array('element' => 'text', 'name' => 'from_email', 'label'=> 'From', 'size' => 30, 'required', 'type' => 'email'); 
 *	$field[] = array('element' => 'text', 'name' => 'message_body', 'label' => 'Comments', 'size' => 60, 'required', 'rows' => 5); 
 *	$field[] = array('element' => 'popup', 'name' => 'importance', 'label' => 'Severity', 'values' => array('L' => 'Low', 'M' => 'Medium', 'H' => 'High')); 
 *	$field[] = array('element' => 'comment', 'value' => 'We will not obtain personally identifying information 
 *	about you when you visit our site unless you choose to provide such information to us. 
 *	If you choose to send e-mail to the site webmaster or submit an online feedback form, 
 *	any contact information that you provide will be used solely to respond to your request.');
 *
 *	An alternate format is now available that make the code a bit easier to read. 
 *	The previous example in this new format would look like:
 *
 *	$field = array(
 *		'element:text    | name:subject      | value:Feedback | hidden',
 *		'element:text    | name:to           | label:To       | value:Feedback',
 *		'element:text    | name:from_email   | label:From     | size:30 | required | type:email',
 *		'element:text    | name:message_body | label:Comments | size:60 | required | rows:5',
 *		'element:popup   | name:importance   | label:Severity | values:(L:Low ~ M:Medium ~ H:High)',
 *		'element:comment | value:We will not obtain personally identifying information 
 *			about you when you visit our site unless you choose to provide such information to us. 
 *			If you choose to send e-mail to the site webmaster or submit an online feedback form, 
 *			any contact information that you provide will be used solely to respond to your request.'
 *	);
 * 
 * This array of fields shows field type, name, default values, various attribues like size or validation type where appropriate
 * This array is then passed to the complete method to display and validate the form. 
 * When the form is fully validated, the method returns true
 *
 * The function getFieldPairs($field) can be used to obtain the name => value pairs returned by the form
 *
 * @author	Alex Bentley
 * @history	7.2		added encryption on upload as an option.
 *			7.1		fully implemented filter_var for data validation
 *			7.0		embedded support for compound names in form creation and verification
 *			6.0     Converted all tag calls to short tags
 *          5.19    updated the processing of the action option
 *          5.18	Fixed a comparison bug where an invalid value would match a 0 value
 *			5.17	fixed a minor bug in composite fields
 *			5.16	removed requiredyears and requiredmonths since they now work correctly and fixed FORM::data
 *			5.15    new data type range which is a set of numeric values from start to stop in step intervals.
 *			5.14	added new form data types required months and required years
 *			5.13	added new option for maxlength attribute
 *			5.12	improved name processing
 *			5.11	fixed getFieldPairs
 * 			5.10	added new data type ext-years
 *			5.9		fixed required field default if no value is specified
 *			5.8		logic flaw in handleUpload
 *			5.7		form tag improvements
 *			5.6		fix to PARAM value for submitted forms
 *			5.5		major rewrite for displayEdit and composite field processing
 *			5.4		fixes to standardize element creation
 *			5.3		fixed wrong code for generating checkboxes and radio buttons to support labels
 *			5.2		changed submitname to submit-name and suppress-submit to submit-suppress
 *			5.1		value fix for composite fields
 *			5.0		removed javascript as a form element, added native support for all javascript actions directly
 *			4.28	using new functions from HTML openTag, closeTag, tag
 *			4.27	eliminated size for hidden fields
 *			4.26	code cleanup
 *			4.25	enhancement to composite field returned values
 *			4.24	fixes to composite field naming and code cleanup and additions to getFieldPairs
 *			4.23	added new predefined values for hours and minutes
 *			4.22	more documentation
 *			4.21	error message update for uploaded files
 *			4.20	more default documentation
 *			4.19	Documentation additions
 *			4.18	added new function editInlineLink for edit-in-line features
 *			4.17	improved character handling
 *			4.16	more error handling options
 *			4.15	fixes to displayEdit and supporting functions regarding values and now urlencodes the target of the form appropriately
 *			4.14	minor update to displayEdit regarding delete link and display items
 *			4.13	support for new value get("environment-params") to add to all displayEdit calls
 *			4.12	fix to displayEdit to check for existence of edit param
 *			4.11	fix to checkbox display
 *			4.10	fix to form tag generation 
 *			4.9		updates to popup and start functions
 *			4.8		new function allowEdit
 *			4.7		fix to required popups using defined fields
 *			4.6		added expiration
 *			4.5		move composite fields to conf
 *			4.4		new support for field type definitions
 *			4.3		new function displayEdit
 *			4.2		fix to normailzation
 *			4.1		normalize pop up array values
 *			4.0		added composite fields and standard popup values
 *			3.9		added dummy required field value
 *			3.8		fixed site functions
 *			1.0		initial release
 */
 
class FORM {

/**
 * returns the field definition for defined fields in conf/site.php
 *
 * @param	$type	the field type you are looking for
 * @return	the defined field definition.
 */
function definedFields($type, $field=array()) {
	
	$default = array_extract(get('defined-fields'), array($type), false); 
	
	if ($default) {
		$default['name'] = $type;
		return array_merge($default, $field);
	} else {
		return $field;
	}
}

/**
 * Returns the HTML for the beginning of a form.
 *
 * @param	$options	an array or coded string of settings for this form.
 * @return	the HTML for the start of an HTML form.
 */
function start($options) {
	if (is_string($options)) $options = strtoarray($options);
    
	$result = '';
	$attrs = array();
	foreach (array('action', 'method', 'class', 'enctype', 'style', 'id', 'name', 'rel') as $element) {
		$value = array_extract($options, array($element), '');
        if ($value != '') {
            if ($element == 'action') {
                if (!str_begins($value, '?')) $value = '?'.$value;
				$page = substr($value, 1);
				
				if (($script = page('script')) != 'index.php') $value = $script.$value; // add in the script name if necessary
				if (($path = page('path')) != '/') $value = $path.$value; // add in a path if necessary
				
                $attrs[$element] = $value;
            } else {
                $attrs[$element] = $value;
            }
        }
	}
	$result .= form($attrs);
	$result .= div();
	if (($options['method'] == 'get')) $result .= input('name:'.$page.' | type:hidden');
    
	return $result;
}

/**
 * Returns the HTML for the submit element of a form.
 *
 * @param	$options	an array or coded string of settings for this form.
 * @return	the HTML for the submit element of this form.
 * @see		tag
 */
function submit ($options) {
    if (is_string($options)) $options = strtoarray($options);

	$result = '';
	$submitDefaults = array(
        'type'          => 'submit', 
        'submit-class'  => 'field', 
        'submit-name'   => 'submit', // this is the internal name that is checked
        'submit'        => 'Submit', // this is the text on the button 
        'submit-label'  => '&nbsp;',
        'submit-id'     => 'submit',
    );
        
    $options = array_merge($submitDefaults, $options);
    
	foreach (HTML::jsattributes() as $f) { 
        $value = array_extract($options, array($f), ''); 
        if ($value != '') $options[$f] = $value; 
    }

	if (!$options['submit-suppress']) {	
		$contents = '';
		if ($options['reset']) $contents .= input('name:reset | type:reset | value:Reset');
        
        $map = array(
            'type'          => 'type',
            'submit-class'  => 'class',
            'submit-name'   => 'name',  // this is the internal name that is checked
            'submit-id'     => 'id',
            'submit'        => 'value', // this is the text on the button
        );
		$inpAttrs = array();
        foreach ($map as $opt => $name) $inpAttrs[$name] = $options[$opt];
        if ($options['submit-label'] != '') $contents .= label('', $options['submit-label']);
        $contents .= input($inpAttrs);
		
		$result .= div('class:'.$inpAttrs['class'], $contents);
	}
	
	return $result;
}

/**
 * Returns the HTML for the end of a form.
 *
 * @return	the HTML for the end of an HTML form.
 */
function endform() {
	return div('/').form('/');
}

/**
 * This function defines the default values for each of the types of items.
 *
 * @details	all elements
 *		value;		PARAM;				PARAM is a special value that means to use the parameter value; otherwise use the value of this item.
 *		class;		field;				the most common alternate value is inline; this allows for multiple fields on the same line.
 *		id;			;					if not set; the name is used for the id.
 *		label;		;					the label text for this item.
 *		name;		;					the internal name for the field.
 *		type;		;					an optionally defined type which can be used for validation purposes.
 *		title;		;					this text is placed in the title attribute of this item.
 *		required;	false;				a boolean used during validation and display to make sure user enters information.
 *		fixed;		false;				a boolean to indicate if this item should be fixed to the passed value.
 *		display;	false;				a boolean to indicate if this item is only presented during display within displayEdit.
 *		style;		padding-right: 1em;	this text is placed in the style attribute of this item.
 *		prefix;		;					during display; put this text before the value.
 *		suffix;		;					during display; put this text after the value.
 *		hidden;		false;				a boolean to indicate if this item is not visible to the user.
 
 * @details	text elements
 *		size;		30;                 the size of this text field in characters.
 *		rows;		0;                  the height of this text field in lines.
 *		password;	false;              a boolean to indicate if this is a password field.
 *		richtext;	false;              a boolean to indicate if we should support richtext editing for allowEdit.
 *		maxlength;	-1;                 the length of text allowed in the input.
 
 * @details	checkbox or radio elements 
 *		group;      NULL;               the name of the group for this item.
 *		min;        0;                  the minimum value.
 *		max;        1;                  the maximum value.
 
 * @details	fileselect elements 
 *		size;		20;                 the size of this text field in characters.
 
 * @details	comment elements 
 *		class;      comment;            for comments the class defaults to comment.
 
 * @details	popup elements 
 *		normalize;	false;              a boolean indicating if the values array should be normalized prior to use.
 *		default;	;                   an optional value to indicate what item to use if no value is specified.
 
 * @details listbox elements
 *		normalize;	false;              a boolean indicating if the values array should be normalized prior to use.
 *      size;       5;                  the number of items to display;
 *      default;    ;                   what to return if no value is selected.
 
 * @details	button elements 
 *		type;       submit;             could be submit or image which then requires a src and onclick="submitform()".
 *      label;      &nbsp;              if you want an inline button with a field with no label set the label to ''.
 *
 * @details	composite elements 
 *		format;		;                   a value indicating special formatting { timestamp | time }.
 *		base;		;                   a value for time formatted items to define what field to use as a timestamp base or 'now'.
 *
 * @details	combined elements 
 *		format;		;                   a value indicating special formatting { timestamp | time }.
 */
function applyDefaults($def, $type, $options='') {
	$defaults = 'value:PARAM | class:field | id: | label: | name: | type: | title: | readonly: | required:false | fixed:false | display:false | style: | prefix: | suffix: | hidden:false';
	
	switch ($type) {
		case 'text':
			$extras = 'size:30 | rows:0 | password:false | richtext:false | maxlength:-1';
			break;
			
		case 'checkbox':
		case 'radio':
			$extras = 'group:null | min:0 | max:1 | style:';
			break;

		case 'fileselect':
			$extras = 'size:20';		
			break;

		case 'comment':
			$extras = 'class:comment';
			break;
		
		case 'popup':
			$extras = 'normalize:false | default:';
			break;
            
		case 'listbox':
			$extras = 'normalize:false | default: | size:5 | multiple:multiple';
			break;
            
        case 'button':
			$extras = 'type:submit | label:&nbsp;';
            break;
            
        case 'group':
			$extras = 'min:0 | max:'.PHP_INT_MAX;
            break;
            
		default:
			$extras = '';
	}
	if (is_string($def)) $def = strtoarray($def);
    
	$def = smart_merge(strtoarray($defaults.' | '.$extras), $def);
	if (!in_array($type, array('comment', 'html', 'placeholder', 'group', 'group-end')) && ($def['name'] == '')) {
		//echo p('style:color:red;', "ERROR: $type field requires a name value");
		return;
	}
	//if (in_array($type, array('checkbox', 'radio')) && ($def['id'] == '') && array_key_exists('name', $def)) $def['id'] = $def['name'];
		
	// get the value for all elements
	if (is_string($options)) $options = strtoarray($options);
	$options = smart_merge(self::formDefaults(), $options); // merge both arrays
	
	if (in_array($type, array('text', 'checkbox', 'radio', 'popup', 'fileselect'))) {
		if ($options == array()) {
			$def['value'] = 'PARAM';
		} else {
			$sub = param($options['submit-name']); // get submit value
			if (($sub != '') && ($sub == $options['submit']) && !$def['fixed']) $def['value'] = 'PARAM';
		}
	}

	if ($def['value'] === 'PARAM') {
		// get request passed parameter as value
		$dv = param($def['name']);
		if (array_key_exists('name', $def) && ($def['name'] != '') && param($def['name'], 'exists')) {
			if (is_string($dv)) $def['value'] = html_entity_decode($dv);
		} else {
			$def['value'] = $dv;
		}
	} else {
		if (is_array($def['value'])) $def['value'] = array_extract($def['value'], array($def['name']), '');
		
		if (($type == 'popup') && ($def['value'] == '')) {
			if (!$def['required']) {
				$def['value'] = array_shift(array_keys((array)$def['values'])); // choose the first if none is specified
			}
		}

	}

	$def['element-class'] = self::getClass($def);
	if (in_array($type, array('checkbox', 'radio'))) $def['checked'] = in_array($def['value'], array('on', 1)) ? 'checked' : '';

	$label = array_extract($def, array('label'), '');
	
	if ($label) {
		$class = $def['required'] ? 'required' : '';
		$labelattrs = array('class' => $class);
		if (!$def['display']) $labelattrs['for'] = $def['id'];
		$def['label-tag'] = label($labelattrs, $def['label']);
	} else {
		$def['label-tag'] = '';
	}

    return $def;
}

/**
 * Predefines some basic pop up values, and includes all values defined in conf/site.php.
 *
 * types defined in this method are:
 *	salutation, states, months, days, years, hours, minutes, expiration
 *
 * @param	$type	the name of the data type for the popup.
 * @param	$start	the starting value for numeric type.
 * @param	$stop	the stopping value for numeric type.
 * @param	$step	the interval betwenn values for numeric type.
 * @return	the values array for this type.
 */
function data ($type, $start=0, $stop=0, $step=0) {
	
	switch($type) {
		case 'salutation':
			return	array(
						'Mr' => 'Mr.', 
						'Ms' => 'Ms.', 'Miss' => 'Miss', 'Mrs' => 'Mrs.', 
						'Dr' => 'Dr.',
					);
			break;
			
		case 'states':
			return	array(
						'AL' => 'Alabama', 'AK' => 'Alaska', 'AS' => 'American Samoa', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
						'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 
						'DE' => 'Delaware', 'DC' => 'DC',  
						'FL' => 'Florida', 
						'GA' => 'Georgia', 'GU' => 'Guam',           
						'HI' => 'Hawaii', 
						'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 
						'KS' => 'Kansas', 'KY' => 'Kentucky',
						'LA' => 'Louisiana',
						'ME' => 'Maine', 'MH' => 'Marshall Islands', 'MD' => 'Maryland', 'MA' => 'Massachusetts', 'MI' => 'Michigan', 'FM' => 'Micronesia', 
						'MN' => 'Minnesota', 'MS' => 'Mississippi', 'MO' => 'Missouri', 'MT' => 'Montana', 
						'NE' => 'Nebraska', 'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York',
						'NC' => 'North Carolina', 'ND' => 'North Dakota', 'MP' => 'N. Mariana Islands', 
						'OH' => 'Ohio', 'OK' => 'Oklahoma', 'OR' => 'Oregon', 
						'PW' => 'Palau', 'PA' => 'Pennsylvania', 'PR' => 'Puerto Rico',
						'RI' => 'Rhode Island',
						'SC' => 'South Carolina', 'SD' => 'South Dakota', 
						'TN' => 'Tennessee', 'TX' => 'Texas', 
						'UT' => 'Utah', 
						'VT' => 'Vermont', 'VA' => 'Virginia', 'VI' => 'Virgin Islands', 
						'WA' => 'Washington', 'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming',
						'AA' => 'AA', 'AE' => 'AE', 'AP' => 'AP', 
						'Not in the US'  => 'N/A',
					);
			break;
			
		case 'months':
			return	array(
						'01' => 'January',		'02' => 'February',		'03' => 'March',		'04' => 'April', 
						'05' => 'May',			'06' => 'June',			'07' => 'July',			'08' => 'August', 
						'09' => 'September',	'10' => 'October',		'11' => 'November',		'12' => 'December',
					);
			break;
			
		case 'optionalmonths':
			return	array(
						'00' => 'Any',
						'01' => 'January',		'02' => 'February',		'03' => 'March',		'04' => 'April', 
						'05' => 'May',			'06' => 'June',			'07' => 'July',			'08' => 'August', 
						'09' => 'September',	'10' => 'October',		'11' => 'November',		'12' => 'December',
					);
			break;
		case 'monthsNoPrecZero':
			return	array(
						'1' => 'January',		'2' => 'February',		'3' => 'March',		'4' => 'April', 
						'5' => 'May',			'6' => 'June',			'7' => 'July',			'8' => 'August', 
						'9' => 'September',     '10' => 'October',		'11' => 'November',		'12' => 'December',
					);
			break;
		
		case 'minutes':
			return	array('00' => '00', '15' => '15', '30' => '30', '45' => '45',);
			break;
		case 'hours':
			$data = array();
			for ($hour = 0; $hour <= 23; $hour++) {
				$ampm = ($hour < 12) ? 'am' : 'pm';
				$dhour = $hour;
				if ($hour == 0) $dhour = 12;
				if ($hour > 12) $dhour = $hour - 12;
				$data[$hour] = sprintf('%02d %s', $dhour, $ampm);
			}
			return $data;
			break;
					
		case 'days':
			$data = array();
			for ($day = 1; $day <= 31; $day++) $data[$day] = sprintf('%02d', $day);
			return $data;
			break;
		
		case 'optionaldays':
			$data = array();
			$data['00'] = ' ';
			for ($day = 1; $day <= 31; $day++) $data[$day] = sprintf('%02d', $day);
			return $data;
			break;
		
		case 'years':
			$data = array();		
			for ($year = 2004; $year <= date("Y")+3; $year++) $data[$year] = $year;
			return $data;
			break;
			
		case 'currentyears':
			$data = array();		
			for ($year = 2004; $year <= date("Y"); $year++) $data[$year] = $year;
			return $data;
			break;
			
		
		case 'optionalyears':
			$data = array();
			$data['00'] = ' ';		
			for ($year = 2004; $year <= date("Y")+3; $year++) $data[$year] = $year;
			return $data;
			break;			
		
		case 'ext_years':
			$data = array();		
			for ($year = 1950; $year <= date("Y")+4; $year++) $data[$year] = $year;
			return $data;
			break;
			
		case 'birth_years':
			$data = array();
			for ($year = date('Y')-100; $year <= date('Y'); $year++) $data[$year] = $year;
			return $data;
			break;
		
		case 'expiration':
			$data = array();		
			for ($year = date("Y"); $year <= date("Y")+10; $year++) $data[$year] = $year;
			return $data;
			break;
					
		case 'range':	
			$values = array();
			for ($i = $start; $i <= $stop; $i += $step) {
				$values[$i] = $i;
			}
			return $values;
			break;

		default:
			$formvalues = get('form-values');
			if (is_array($formvalues) && array_key_exists($type, $formvalues)) {
				return $formvalues[$type];
				break;
			}
			return false;
	}
}

/**
 * Expands a composite field definition into the component fields.
 *
 * @param	$type	the name of the composite field type.
 * @param	$field	the name of the field.
 * @return	an array of field definitions that correspond to the combined field definition.
 */
function combinedDefinition($type, $field) {	
    static $combinedFields = NULL;
    
    if ($combinedFields == NULL) $combinedFields = get('combined-fields'); // load once
    
    $fields = array();
    if (array_key_exists($type, $combinedFields)) {
        $fields[] = array('element' => 'group');
        
        $label = array_extract($field, array('label'), '');
        $name  = array_extract($field, array('name'), '');
        
        $fields = $combinedFields[$type];

        foreach ($fields as $fielddef) {
            if (array_key_exists('type', $fielddef)) $fielddef = self::definedFields($fielddef['type'], $fielddef);
            
            $fielddef['name'] = $name.'['.$fielddef['name'].']';
            $fielddef['value'] = param($fielddef['name'], 'value', $fielddef['value']);
                                    
            $fields[] = $fielddef;
        }
        
        $fields[] = array('element' => 'group-end');
    }
    
    return $fields;
}

/**
 * Expands a composite field definition into the component fields.
 *
 * @param	$type	the name of the composite field type.
 * @param	$field	the name of the field.
 * @return	an array of field definitions that correspond to the composite field definition.
 */
function compositeDefinition($type, $field) {	
	static $compositeFields = NULL;
	
	if ($compositeFields == NULL) $compositeFields = get('composite-fields'); // load once
	
	$fields = array();
	$fields[] = array('element' => 'group');
	
	$label = array_extract($field, array('label'), '');
	$name  = array_extract($field, array('name'), '');
	$base  = array_extract($field, array('base'), '');
	
	if (array_key_exists($type, $compositeFields)) {
		$fields = $compositeFields[$type];
		$values = array();
		foreach ($fields as $key => $fielddef) {
			//if (array_key_exists('value', $field)) $fielddef['value'] = $field['value']; // transfer value to subfields
			
			if (array_key_exists('name', $fielddef)) {
				$localname = '';
				if (str_begins($fielddef['name'], ':')) $localname = str_replace(':', '', $fielddef['name']);
				if (array_key_exists($localname, $values)) {
					$fielddef['value'] = $values[$localname]; // transfer as valid
				}
				
				if ($localname == 'format') {
					$format = $fielddef['value'];
					switch ($format) {
						case 'datetime':
						case 'timestamp':
						case 'time':
							$value = $field['value'];
							if (is_array($value)) $value = $value[$field['name']];
							$values = getdate($value);
							$values['month'] = $values['mon'];
							$values['day'] = $values['mday'];
							$values['hour'] = $values['hours'];
							$values['minute'] = $values['minutes'];
							break;
						default;
					}
				}
			}
			if (array_key_exists('type', $fielddef)) $fielddef = self::definedFields($fielddef['type'], $fielddef);
			foreach (array('name' => $name, 'label' => $label) as $item => $parentdata) {
				if (array_key_exists($item, $fielddef) && str_begins($fielddef[$item], ':')) {
					$fielddef[$item] = str_replace(':', $parentdata.':', $fielddef[$item]);
				}
			}

			if (array_key_exists('value', $fielddef) && !is_array($fielddef['value']) && str_contains($fielddef['value'], '$base')) {
				$fielddef['value'] = str_replace('$base', $base, $fielddef['value']);
			}
			
			$fields[$key] = $fielddef;
		}
	}
	
	$fields[] = array('element' => 'group-end');

	return $fields;
}

/**
 * Goes through all the field definitions and expands all composite fields.
 *
 * @param	$fields	the array of field definitions for a form.
 * @return	the expanded field definitions.
 * @see		compositeDefinition
 * @see		definedFields
 */
function expandFields($fields) {
	$expanded = array();
	$envParams = page('environment');
	if (!$envParams) $envParams = array();
	
    foreach ($fields as $field) {
		if (is_string($field)) $field = strtoarray($field);
		
		@list($element, $subtype) = explode(':', $field['element']);
		if ($element == 'combined') {
			$flds = self::combinedDefinition($subtype, $field);
			
			foreach ($flds as $fld) {
				$fld = smart_merge($field, $fld);
				$expanded[] = $fld;
			}	
			
		} else if ($element == 'composite') {
			$flds = self::compositeDefinition($subtype, $field);
			
			foreach ($flds as $fld) {
				$fld = smart_merge($field, $fld);
				$expanded[] = $fld;
			}	
			
		} else {
			if (array_key_exists('type', $field)) {
				$field = self::definedFields($field['type'], $field);
			}
			$expanded[] = $field;
		}
        if (array_key_exists('name', $field) && is_array($envParams)) {
            $name = $field['name'];
            $key = array_search($name, $envParams);
            if ($key) unset ($envParams[$key]);
        }
	}
	
	foreach ((array)$envParams as $param) $expanded[] = array('element' => 'text', 'hidden', 'name' => $param);
	
	return $expanded;
}

/**
 * Generates the HTML for a form text element based on the field definition.
 *
 * @param	$def	the field definition for this field.
 * @return	the HTML for this form field.
 */
function text($def) {
	$result = '';
	if(!isset($def['rel'])){
		$def['rel']='';
	}
	if ($def['richtext']) {
		$def['richtext'] = 'true';
	} else {
		$def['richtext'] = '';
	}
	
	$type = 'text';
	if (!isset($def['name'])) $def['display'] = true;
	if ($def['password']) $def['type'] = 'password';
	if ($def['hidden']) {
		$def['type'] = 'hidden';
		$def['class'] = 'hidden';
	}
		
	if (!$def['hidden']) $result .= div('class:'.$def['element-class']);
	
	$result .= $def['label-tag'];

	if ($def['type'] == 'hidden') $def['size'] = '';
		
	if ($def['display']) {
		if ($def['value'] != '') $result .= p('', $def['value']);
		
	} else {
		if (!in_array($def['type'], array('password', 'hidden'))) $def['type'] = 'text';
		$attrs = array('contentEditable' => $def['richtext']);
		foreach (array('name', 'id', 'class', 'style', 'title', 'type', 'rel', 'readonly') as $f) $attrs[$f] = $def[$f];
		foreach (HTML::jsattributes() as $f) { $value = array_extract($def, array($f), ''); if ($value != '') $attrs[$f] = $value; }

		if ($def['rows'] > 0) {
			$attrs['cols'] = $def['size'];
			$attrs['rows'] = $def['rows'];
			$tag = 'textarea';
			$contents = htmlentities($def['value']);
			if ($contents == null) $contents = '';
			unset($attrs['type']);
		} else {
			$attrs['size'] = $def['size'];
			$attrs['value'] = htmlentities($def['value']);
			if ($def['maxlength'] > 0) $attrs['maxlength'] = $def['maxlength'];
			$tag = 'input';
			$contents = '';
		} 

		$result .= tag($tag, $attrs, $contents);
	}
	
	if (!$def['hidden']) $result .= div('/');

	if (array_key_exists('error', $def)) $result = div('class:'.$def['error'], $result);

	return $result;
}

function badListValue() {
	return '~@~';
}

/**
 * Generates the HTML for a form popup or select element based on the field definition.
 *
 * @param	$def	the field definition for this field.
 * @return	the HTML for this form field.
 */
function popup($def) {
	$result = '';
	if ($def['hidden']) {
		echo input('name:'.$def['name'].' | type:hidden | value:'.$def['value']);
		return;
	}
    
	if(!isset($def['rel'])) $def['rel']='';
    
	$def = self::expandListValues($def);
    
	if ($def['element'] == 'popup') {
        $def['size'] = 0;
        $def['multiple'] = '';
        $popstart = '';
        if ($def['required']) {
            $reqd_options = array();
            if (!in_array('Select one', $def['values'])) $reqd_options['value'] = self::badListValue();
            if (!param('submitted', 'exists')) $reqd_options['selected'] = 'selected';
            
            $popstart = option($reqd_options, 'Select one'); // stick in a non-validatible entry
        }
    } else {
        $def['name'] .= '[]';
    }
    
	$result .= div('class:'.$def['element-class']);
	$result .= $def['label-tag'];

	$attrs = array();
	foreach (array('name', 'id', 'style', 'title', 'type', 'rel', 'readonly', 'multiple', 'size') as $f) $attrs[$f] = $def[$f];
	foreach (HTML::jsattributes() as $f) { $value = array_extract($def, array($f), ''); if ($value != '') $attrs[$f] = $value; }
	
	$result .= "\n".select($attrs)."\n";
	$result .= $popstart;
	
	if (!str_contains($def['name'], '-')) {
		foreach ($def['values'] as $option => $optval) {		
			if (is_array($optval)) {
				$result .= optgroup('label:'.$option);
				
				foreach ($optval as $key => $value) { // use array name as prefix for returned value
					if ($value == $def['value']) {
						$result .= option('selected:selected | value:'.$option.':'.$value, $value);
					} else {
						$result .= option('value:'.$option.':'.$value, $value);
					}
				}
				$result .= optgroup('/');

			} else {
				if (($option !== false) && ((string)$option == (string)$def['value'])) {
					$result .= option('selected:selected | value:'.$option, $optval);
				} else {
					$result .= option('value:'.$option, $optval);
				}
			}
		}
	} else {
		foreach ($def['values'] as $option => $optval) {		
			if (is_array($optval)) {
				$result .= optgroup('label:'.$option);

				foreach ($optval as $key => $value) { // use array name as prefix for returned value
					if ($key === $def['value']) {
						$result .= option('selected:selected | value:'.$key, $value);
					} else {
						$result .= option('value:'.$key, $value);
					}
				}
				$result .= optgroup('/');
				
			} else {
				if (($option !== false) && ($option === $def['value'])) {
					$result .= option('selected:selected | value:'.$option, $optval);
				} else {
					$result .= option('value:'.$option, $optval);
				}
			}
		}
	}
	$result .= select('/').div('/');
	
	if (array_key_exists('error', $def)) $result = div('class:'.$def['error'], $result);

	return $result;
}

/**
 * Generates the HTML for a form checkbox element based on the field definition.
 *
 * @param	$def	the field definition for this field.
 * @return	the HTML for this form field.
 */
function checkbox($def) {
	if(!isset($def['rel'])){
		$def['rel']='';
	}	
	$attrs = array('type' => 'checkbox', 'class' => 'checkbox', 'checked' => $def['checked'], 'readonly' => $def['readonly']);
	foreach (array('name', 'id', 'title', 'style', 'rel') as $f) $attrs[$f] = $def[$f];
	foreach (HTML::jsattributes() as $f) { $value = array_extract($def, array($f), ''); if ($value != '') $attrs[$f] = $value; }
	
	$result = div('class:'.$def['element-class'].' | title:'.$def['title'].' | style:'.$def['style'],
		div('class:checkradio', input($attrs).$def['label-tag']));
			
	if (array_key_exists('error', $def)) $result = div('class:'.$def['error'], $result);

	return $result;
}

/**
 * Generates the HTML for a form radio button element based on the field definition.
 *
 * @param	$def	the field definition for this field.
 * @return	the HTML for this form field.
 */
function radio($def) {
	if(!isset($def['rel'])){
		$def['rel']='';
	}	
	if (array_key_exists('values', $def)) {
		$temp = $def;
		unset($temp['values']);
		$result = '';
		foreach ((array)$def['values'] as $index => $value) {
			$temp['value'] = $value;
			if (is_array($def['label'])) {
				$temp['label'] = $def['label'][$index];
				$label = $temp;
				foreach ($label as $k => $v) if (!in_array($k, array('class', 'id', 'title'))) unset($label[$k]);
				$temp['label-tag'] = label($label, $temp['label']);
				if ($def['value'] == $value) $temp['checked'] = 'checked';
			} else {
				$temp['label'] = $value;
			}
			$result .= self::radio($temp, 'radio');
			unset($temp['checked']);
		}
		return $result;
	}
	
	$attrs = array('type' => 'radio', 'class' => 'checkbox', 'checked' => $def['checked'], 'readonly' => $def['readonly']);
	foreach (array('name', 'id', 'title', 'style', 'rel', 'value') as $f) $attrs[$f] = $def[$f];
	foreach (HTML::jsattributes() as $f) { $value = array_extract($def, array($f), ''); if ($value != '') $attrs[$f] = $value; }
	
	$result = div('class:'.$def['element-class'].' | title:'.$def['title'], div('class:checkradio', input($attrs).$def['label-tag']));
			
	if (array_key_exists('error', $def)) $result = div('class:'.$def['error'], $result);

	return $result;
}


/**
 * Generates the HTML for a form file selection element based on the field definition.
 *
 * @param	$def	the field definition for this field.
 * @return	the HTML for this form field.
 */
function fileselect($def) {
	if(!isset($def['rel'])){
		$def['rel']='';
	}	
	$attrs = array('type' => 'file', 'class' => $def['element-class']);
	foreach (array('name', 'id', 'title', 'value', 'size', 'style', 'rel', 'readonly') as $f) $attrs[$f] = $def[$f];
	foreach (HTML::jsattributes() as $f) { $value = array_extract($def, array($f), ''); if ($value != '') $attrs[$f] = $value; }
	
	$result = div('class:'.$def['class'], $def['label-tag'].input($attrs));
	
	if (array_key_exists('error', $def)) $result = div('class:'.$def['error'], $result);

	return $result;
}

/**
 * Returns the HTML for a buttom element of a form.
 *
 * @param	$options	an array or coded string of settings for this form.
 * @return	the HTML for the buttom element.
 * @see		tag
 */
function button ($options) {
    if (is_string($options)) $options = strtoarray($options);
    $result = '';
    
    foreach (HTML::jsattributes() as $f) { 
        $value = array_extract($options, array($f), ''); 
        if ($value != '') $options[$f] = $value; 
    }
    
    $result .= $def['label-tag'].div('class:'.$options['class'], input($options));
    
    return $result;
}

    
/**
 * Generates the HTML for a form comment element based on the field definition.
 *
 * @param	$def	the field definition for this field.
 * @return	the HTML for this form field.
 */
function comment($def) {
	return p('class:'.$def['class'], $def['value']);
}

/**
 * Generates the HTML for inserted HTML based on the field definition.
 *
 * @param	$def	the field definition for this field.
 * @return	the HTML for this form field.
 */
function html($def) {	
	return $def['value'];
}

/**
 * Generates the HTML for a form placeholder element based on the field definition.
 *
 * @param	$def	the field definition for this field.
 * @return	the HTML for this form field.
 */
function placeholder($def) {
	return div('class:hidden', input('name:placeholder | type:text'));
}	


/**
 * Generates the class for a form element based on the field definition.
 *
 * @param	$def	the field definition for this field.
 * @return	the class for this field.
 */
function getClass($def) {
	$class = $def['class'];
	
	if ($class == '') {
		// see if required is conditional
		if (($def['required'] !== true) && ($def['required'] !== false) && ($def['required'] != '')) {
			eval('$required = '.$def['required'].';');
		}
		
		$class .= 'field';
	}
	
	return $class;
}

/**
 * Creates or processes the submit w/validation for a form definition.
 *
 * @param	$fielddefs	the array of field definitions for this form.
 * @param	$options	the form options array.
 * @return	a boolean indicating if all required fields were entered.
 */
function complete($fielddefs, $options='') {
	if (is_string($options)) $options = strtoarray($options);
	$options = smart_merge(self::formDefaults(), $options); // merge both arrays
	
	$result = self::verify($fielddefs, $options);
		
	if (!$result['valid'] || $options['redisplay']) {
		echo $result['errors'];
		echo self::display($fielddefs, $options);
	}
	
	return $result['valid'];
}

/**
 * Defines the default values for a form.
 *
 * @details	method;			post;							should be 'post' or 'get'.
 *			action;			THIS;							what url to submit to, 'THIS' means use the current page.
 *			redisplay;		false;							a boolean to indicate if the form should be redisplayed when complete. 
 *			submit;			Submit;							the text of the submit button.
 *			reset;			false;							a boolean to indicate if a reset button should be displayed.
 *			submit-name;	submit;							the name of the submit parameter (generally not changed).
 *			submit-class;	field;							the CSS class of the submit button.
 *			submit-id;      ;                               the id of the submit button.
 *			submit-suppress;false;							a boolean to indicate if the submit button should not be displayed.
 *			enctype;		application/x-www-form-urlencoded;	file uploads use 'multipart/form-data' automatically.
 *			suppress-empty;	true;							a boolean to indicate if empty fields should not be displayed in displayEdit.
 *			id;				;								this text is used for the attribute in the form tag.
 *			class;			form;							this text is used for the attribute in the form tag.
 *			style;			;								this text is used for the attribute in the form tag.
 *			edit;			false;							a boolean to indicate if edit is forced in displayEdit.
 *
 * @return	the array of values.
 */
function formDefaults() {
	$defaults = array(
		'method'            => 'post', 
		'action'            => 'THIS', 
		'redisplay'         => false, 
		'submit'            => 'Submit', 
		'reset'             => false, 
		'submit-name'       => 'submit', 
        'submit-class'      => 'field',
        'submit-id'         => '',
		'submit-suppress'   => false,
		'enctype'           => 'application/x-www-form-urlencoded',
		'suppress-empty'    => true, 
		'id'                => '', 
		'class'             => 'form', 
		'style'             => '',
		'edit'              => false,
		'rel'              	=> '',
	);
//	foreach (HTML::jsattributes() as $jsa) $defaults[$jsa] = '';
	
	return $defaults;
}

/**
 * Gets predefined popup values and replaces them in the file def.
 *
 * @param	$def	the field definition.
 * @return	the updated field definition.
 */
function expandListValues($def) {
	if (!is_array($def['values'])) {
		$sources = explode(',', $def['values']);
		$def['values'] = array();
		$count = 0;

		foreach ($sources as $source) {
			$count++;
			list($sourcetype, $name) = explode(':', $source);
			if ($sourcetype == 'FORM') {
				if ($count == 1) {
					$def['values'] = self::data($name);
				} else {
					$def['values'] = array_merge($def['values'], self::data($name));
				}
			}
		}
	}
	
	if ($def['normalize']) {
		$values = array_values($def['values']);
		$def['values'] = array_combine($values, $values);
	}
	
	return $def;
}

function normalizeKeys($array) {
	$values = array_values($array);
	return array_combine($values, $values);	
}
	
/**
 * Processes the submit w/validation for a form definition
 *
 * @param	$fielddefs	the field definitions array for this form.
 * @param	$options	the form options array.
 * @return	a result array that includes any errors that may have occured during verfication.
 * @see		expandFields
 * @see		applyDefaults
 */
function verify ($fielddefs, $options='') {
	if (is_string($options)) $options = strtoarray($options);
	$options = smart_merge(self::formDefaults(), $options); // merge both arrays
	
	$result = '';
	$options['submitted'] = false;
	$sub = param($options['submit-name']); // get submit value
	
	// first check to see if there was a type button element and if it was clicked
	foreach ($fielddefs as $def) {
		if (is_string($def)) $def = strtoarray($def);
		if(array_key_exists('element', $def) && isset($def['element'])){
			$def = self::applyDefaults($def, $def['element'], $options);
			if (($def['element'] == 'button') && param($def['name'], 'exists')) {
				$sub = $def['value'];
				break;
			}
		}
	}
	
	if (($sub != '') && ($sub == $options['submit'])) { // this is a submit, attempt to validate all fields
		
		$options['submitted'] = true;
		$checkgroup = array();
		$failed = array();
		$required = array();
		
		$fielddefs = self::expandFields($fielddefs);        
		foreach ($fielddefs as $def) {
            if (is_string($def)) $def = strtoarray($def);
            $def = self::applyDefaults($def, $def['element'], $options);

			$reqd = $def['required'];
            
			$value = html_entity_decode(param($def['name'])); // since form has been submitted, use the passed value, not the default ($def['value'])
			
			if (in_array($def['element'], array('hidden', 'comment', 'group', 'group-end', 'html'))) {
				// don't validate
                if ($def['element'] == 'group') {
                    $checkgroup[$def['name']]['count'] = 0;
                    $checkgroup[$def['name']]['min'] = $def['min'];
                    $checkgroup[$def['name']]['max'] = $def['max'];
                }
			} else {
				if ($def['element'] == 'fileselect') {
					foreach ($_FILES as $file) {
						$value = $file['name'];
						break;
					}
				}
				if ($value == '') { // no value was passed						
					// see if required is conditional
                    if (is_string($reqd)) $reqd = trim($reqd);
					if (!in_array($reqd, array(true, false, ''))) eval('$reqd = '.$reqd.';');
					if ($reqd === true) {
						$required[] = $def['label'];
						$def['title'] = 'This is a required entry.';
						$def['error'] = 'required';
					}
					
				} else {
                    if ($def['element'] == 'checkbox') {
                        if (array_key_exists('group', $def) && !in_array($def['group'], array('null', null))) {
                            if (!array_key_exists($def['group'], $checkgroup)) {
                                // preset values for the group that follows.
                                $checkgroup[$def['group']]['count'] = 0;
                                $checkgroup[$def['group']]['min'] = $def['min'];
                                $checkgroup[$def['group']]['max'] = $def['max'];
                            }
                                
                            if ($value == 'on') $checkgroup[$def['group']]['count']++;
                        }
					} else if (($def['element'] == 'popup') || ($def['element'] == 'listbox')) {
						if ($reqd) {
							$def = self::expandListValues($def);
							if (!array_has_key($value, $def['values']) || str_begins($value, self::badListValue())) {
								$def['title'] = 'You must make a selection from '.$def['label']; // return field label
								$failed[] = $def['title'];
								$def['error'] = 'required';
							}
						}
					} else if ($def['element'] == 'text') {
						if (($def['maxlength'] > 0) && (strlen($value) > $def['maxlength'])) {
							$def['title'] = $def['label'].' is limited to no more than '.$def['maxlength'].' characters';
							$def['error'] = 'invalid';
							$failed[] = $def['title'];
						}
						
						if (($valid = self::validateField($def, $value)) !== true) { 
							$failed[] = $valid; // return field label
							$def['error'] = 'invalid';
						}
					}
				}
			}
	
		}
		// check any checkbox groups
		if (is_array($checkgroup)) {
            foreach ($checkgroup as $def['name'] => $cg) {
				if (($cg['min'] != null) && $cg['count'] < $cg['min']) {
					$failed[]  = 'At least '.$cg['min'].' of the '.$def['name'].' must be checked.';
					$def['error'] = 'invalid';
				} else if (($cg['max'] != null) && $cg['count'] > $cg['max']) {
					$failed[]  = 'No more than '.$cg['max'].' '.$def['name'].' can be checked.';
					$def['error'] = 'invalid';
				}
			}
		}
		
		// build required fields error message
		if (count($required)) {
			$flds = '';
			foreach ($required as $fld) {
				append($flds, $fld, ', ');
			}
			
			switch (count($required)) {
				case 1:
				append($flds, 'is a required field.', ' ');
				break;
				
				case 2:
				$flds = str_replace(',', ' and', $flds);
				append($flds, 'are required fields.', ' ');
				break;
				
				default:
				$flds = substr_replace($flds, ', and', strrpos($flds, ','), 1);
				append($flds, 'are required fields.', ' ');
			}
			$failed[] = $flds;
		}
		
		if (count($failed)) {
			$result .= div(array('id' => 'form-error')).ul();
			for ($i = 0; $i < count($failed); $i++) {
				$result .= li(array('class' => 'form-error'), $failed[$i]);
			}
			$result .= ul('/').div('/');
			
			return array('valid' => false, 'errors' => $result);

		} else {
			return array('valid' => true, 'errors' => '');
		}
	}
}

function arrayKeyExists($test, $values) {
	foreach ((array)$values as $key => $value) {
		if ($test == $key) return true;
		if (is_array($value)) {
			if (self::arrayKeyExists($test, $value)) return true;
		}
	}
	return false;
}	

/**
 * Generates the HTML for a form.
 *
 * @param	$fielddefs	the field definitions array for this form.
 * @param	$options	the form options array.
 * @return	the HTML for this form.
 * @see		expandFields
 * @see		applyDefaults
 */
function display($fielddefs, $options='') {
	$result = '';
	if (is_string($options)) $options = strtoarray($options);
    
	$options = smart_merge(self::formDefaults(), $options); // merge both arrays
    
	$fielddefs = self::expandFields($fielddefs);
    
	// make sure all names are HTML processable
	foreach ($fielddefs as $field) {
		if (array_key_exists('name', $field)) $field['name'] = UTIL::goodName($field['name']);
	}
	
	$options['submitted'] = false;
	$sub = param($options['submit-name']); // get submit value
	
	if (($sub != '') && ($sub == $options['submit'])) $options['submitted'] = true;
	
	$s = page('site', ':');
	$p = page();
	
	// return to this page
	if ($options['action'] == 'THIS') {
		$options['action'] = '?'.$s.$p;
		$pv = page('value');
		if ($pv != '') $options['action'] .= '='.urlencode($pv);
	}
	
	$group = false; // start with no groups
	
	$result .= div('');
	// look for fileselects
	foreach ($fielddefs as $def) {
		if ($def['element'] == 'fileselect') {
            $options['method'] = 'post';
			$options['enctype'] = 'multipart/form-data';
			ini_set('post_max_filesize', '50M');
			break;
		}
	}
	$result .= self::start($options);

	foreach ($fielddefs as $def) {
        $def = self::applyDefaults($def, $def['element'], $options);
		$element = $def['element'];
		
		if ($element == 'group') {
			if ($group == true) $result .= fieldset('/');
			$group = true;
						
			$result .= fieldset(array('id' => $def['id'], 'class' => $def['class']));
			
			if ($def['label'] != '') $result .= legend('', $def['label']);
			
		} else if ($element == 'group-end') {
			if ($group == true) $result .= fieldset('/');
			
			$group = false;

		} else if ($element == 'checkgroup') {
			// don't build anything...for validation only
			
		} else {				
			@list($element, $subtype) = explode(':', $element);
			
			switch($element) {
				case 'text':
					$result .= self::text($def);
					break;
				case 'popup':
				case 'listbox':
					$result .= self::popup($def);
					break;
				case 'radio':
					$result .= self::radio($def);
					break;						
				case 'checkbox':
					$result .= self::checkbox($def);
					break;
				case 'fileselect':
					$result .= self::fileselect($def);
					break;
				case 'button':
					$result .= self::button($def);
					break;
				case 'comment':
					$result .= self::comment($def);
					break;
				case 'html':
					$result .= self::html($def);
					break;
				case 'placeholder':
					$result .= self::placeholder($def);
					break;
				default:
			}
		}
	}
	if ($group == true) $result .= fieldset('/');
	
	$result .= self::submit($options);
	$result .= self::endform();
	$result .= div('/').div('class:formend', ' ');
	return $result;
}

/**
 * Returns a keyed array of fields and values for a form submission.
 *
 * @param	$field	the field definition array.
 * @param	$additions	an array of additional values to include.
 * @return	a keyed array of submitted fields and values.
 * @see		applyDefaults
 */
function getFieldPairs($field, $additions=array()) {
	$fields = null;
	
	$expandedfield = self::expandFields($field);
	
	foreach ($expandedfield as $def) {
		$def = self::applyDefaults($def, $def['element']);
		if (!in_array($def['element'], array('comment', 'html', 'group')) || in_array($def['element'], $additions)) {
			if (in_array($def['element'], array('text')) && ($def['value'] != 'PARAM')) {
				$value = param($def['name'], 'value', $def['value']);
				$value = array_extract($value, array($def['name']), $value);

			} else {
				$value = param($def['name']);
			}
			
			$fields[$def['name']] = $value;
		}
	}
	
	// process any composite fields
	foreach ($field as $def) {
        if (is_string($def)) $def = strtoarray($def);

        if (array_key_exists('element', $def) && str_begins($def['element'], 'composite:')) {
			$fields[$def['name']] = param($def['name']);
		}
	}
	
	// remove environment variables if any
	$envParams = get('environment-params');
	if (is_array($envParams)) foreach ($envParams as $param) unset($fields[$param]);
	
	return $fields;
}

/**
 * Returns the assumed field type if not explicitly set.
 *
 * @param	$name	the name of this field.
 * @param	$type	any explicit type.
 * @return	the determined type.
 */
function getFieldType($name, $type='') {
	$not_validated = 'not validated';
	$fieldtype = $not_validated;
	
	if (!in_array($type, array('', 'none'))) {
		$fieldtype = $type;
	
	} else {
		// search for key terms in field names to determine type
		if ($name == 'id') $fieldtype = 'number';
		if ($name == 'userid') $fieldtype = 'number';
		
		if (str_contains($name, 'name')) $fieldtype = 'string';
		if (str_contains($name, 'email')) $fieldtype = 'email';
		if (str_contains($name, 'date')) $fieldtype = 'date';
		if (str_contains($name, 'phone')) $fieldtype = 'phone';
		if (str_contains($name, 'zip')) $fieldtype = 'zipcode';
		if (str_contains($name, 'ssn')) $fieldtype = 'ssn';
	}
	// assign type based on field type if all else fails
	if (($fieldtype == $not_validated) && ($type != '')) {
		switch($type) {
			case 'number':
			case 'int':
			case 'smallint':
			case 'bigint':
				$fieldtype = 'number';
				break;
				
			case 'date':
			case 'timestamp':
				$fieldtype = 'date';
				break;
				
			default:
		}
	}
	
	return $fieldtype;
}

/**
 * Uses filter_var to validate various field types.
 *
 * @param	$name	the name of this field.
 * @param	$value	the submitted value.
 * @return	a boolean indicating if the value passed validation for this field.
 */
function validateField($def, $value) {	
	$fieldtype = self::getFieldType($def['name'], $def['type']);
	$valid = true;
	$invalidLabel = em($def['name']).' <strong>&ldquo;'.$value.'&rdquo;</strong> is not a valid '.$fieldtype;
	
	switch ($fieldtype) {
		case 'email':
			$email = '/^(['."'".'a-zA-Z0-9_\-])+(\.(['."'".'a-zA-Z0-9_\-])+)*@((\[(((([0-1])?([0-9])?[0-9])|(2[0-4][0-9])|(2[0-5][0-5])))\.(((([0-1])?([0-9])?[0-9])|(2[0-4][0-9])|(2[0-5][0-5])))\.(((([0-1])?([0-9])?[0-9])|(2[0-4][0-9])|(2[0-5][0-5])))\.(((([0-1])?([0-9])?[0-9])|(2[0-4][0-9])|(2[0-5][0-5]))\]))|((([a-zA-Z0-9])+(([\-])+([a-zA-Z0-9])+)*\.)+([a-zA-Z])+(([\-])+([a-zA-Z0-9])+)*))$/';
			$valid = preg_match($email, $value);
			if ($valid) return true;
			
			$valid = $invalidLabel;
			break;
			
		case 'number':
			$options = array();
			if (array_key_exists('min', $def)) $options['min_range'] = $def['min'];		
			if (array_key_exists('max', $def)) $options['max_range'] = $def['max'];
			
			$filter = array(
				'filter' => FILTER_VALIDATE_INT,
				'flags' => FILTER_REQUIRE_SCALAR, 
			);
			if ($options) $filter['options'] = $options;
			
			$valid = filter_var($value, FILTER_DEFAULT, $filter);
			if ($valid) return true;
			
			$valid = $invalidLabel;
			break;
			
			break;
			
		case 'date':
			break;
			
		default:
	}
	
	return $valid;
}

/**
 * Converts a name to one that can be used as a CSS id value.
 *
 * @param	$id	the candidate name.
 * @return	a possibly modified name that works as a CSS id.
 */
function validId($id) {
	$id = str_replace(array(' ', ')', '('), '', $id);
	return $id;
}

/**
 * Encapsulates the processing of file selection form elements.
 *
 * @param	$options	an array of values to use to modify the behavior (see below).
 * @details	file-id;				file;						name of field in form.
 *			file-dir;				get(file_directory_web);	directory to put file into.
 *			filename;				;						what to name the new file. default is to retain the original name.
 *			allowed-extensions;		'*';						an array of allowed extensions.
 *			replace;				false;					a boolean to indicate if we should overwrite an existing file
 *			transfer-method;		move_uploaded_file;		what PHP function to use to process the uploaded file.
 */
function handleUpload($options='') {
	$defaults = array(
		'file-id' => 'file',							// name of field in form
		'file-dir' => get('file_directory'),	// directory to put file into
		'filename' => '',							// retain original file name
		'allowed-extensions' => array('*'),			// allow all
		'replace' => false,						// don't overwrite existing file
		'transfer-method' => 'move_uploaded_file',	// method to use to transfer file to final location
		'unzip' => false,						// unzip a zip file on a completed upload
	);
    
	$options = smart_merge($defaults, strtoarray($options));

	$success = true;
	$reason = '';

	$tempfile = $_FILES[$options['file-id']]['name'];
	if ($options['filename'] == '') $options['filename'] = basename($tempfile);
	
	if ($tempfile == '') {
		$success = false;
		$reason = 'file was too big.';
	}
	
	$ext = FILE::ext($tempfile);
	
	if ($success && !in_array('*', $options['allowed-extensions']) && !in_array($ext, $options['allowed-extensions'])) {
		$success = false;
		$reason = 'uploaded file extension ('.$ext.') is not allowed.';
	}
	
	$name = FILE::name($options['filename']).'.'.$ext;
	
	// make sure upload directory exists
	if ($success) {
		$upload_directory = get('site-directory').$options['file-dir'];
		
		if (!is_dir($upload_directory)) {
			$success = @mkdir($upload_directory);
			if (!$success) $reason = 'directory could not be created';
		}
	}
	
	if ($success) {
		$uploadfile = $upload_directory.'/'.$name;
		// see if there is a collision
		if (file_exists($uploadfile)) {
			if ($options['replace']) {
				$success = @unlink($uploadfile);
				if (!$success) $reason = 'existing file could not be deleted';
			} else {
				$success = false;
				$reason = 'file already exists';
			}
		}
				
		// move file to final location
		$tempfile = $_FILES[$options['file-id']]['tmp_name'];
		$old = substr(sprintf('%o', fileperms('/tmp')), -4);
		@chmod($tempfile, 0777);
		@chmod($upload_directory, 0777);
				
		if ($success) {
			$tm = $options['transfer-method'];
			if (($checkpolicy = get('encryption-policy')) && @call_user_func($checkpolicy, $uploadfile)) $tm = 'encrypt_uploaded_file';
			$success = call_user_func($tm, $tempfile, $uploadfile);
			if (file_exists($uploadfile)) $success = true;
			
			if (!$success) $reason = 'file could not be saved (possibly too large or file security issues)';
		}
		
		if ($success && ($ext == 'zip') && $options['unzip']) {
			$success = FILE::unZip($uploadfile, $upload_directory);
		}
	}

	return array('success' => $success, 'reason' => $reason, 'file' => $name);
}

/**
 * this creates an edit link for edit-in-line feature
 *
 * @param	$url		the base url to use
 * @param	$editparam	the identifier to use for this type of element
 * @param	$id			the value to use to identify this item
 * @see		LINK::name
 * @see		param
 * @see		get
 * @see		LINK::paramtag
 */
function editInlineLink($url, $editparam, $id) {
	LINK::name($editparam);
	echo div('class:edit-option');
	$options = array();
	if (!param($editparam, 'value', false) || param('submit', 'exists')) $options = array($editparam => $id);
	$options['#'] = $editparam;
	
	LINK::paramtag($url, $options, IMG::icon('edit-icon', 'eil'));
	echo div('/');
}

/**
 * Intended to provide a type of edit-in-line capability.
 *
 * It works by creating an expanded form definition using the fields you want to display and passing an array of values associated with those fields.
 * When not editing, it attempts for format the data based on the form definition.
 * When you are editing, it interprets the form definition and displays is.
 * Once the form is submitted, the form data is returned.
 * It is then the responsibility of the caller to update the appropriate data sources.
 *
 * @param	$fields		the field definitions for the data
 * @param	$data		the values for the fields
 * @param	$options	a keyed array of form values to use
 * @see		formDefaults
 * @see		editInLine
 * @see		expandFields
 * @see		getFieldPairs
 * @see		complete
 * @see		smart_merge
 */
function displayEdit($sourcefields, $data, $options=array()) {
	if (is_string($options)) $options = strtoarray($options);
	$options = smart_merge(self::formDefaults(), $options); // merge both arrays
	
	$result = false;
	$edit = ((param($options['id'], 'exists') && (param($options['id']) == $data['id'])) || $options['edit']);
	if ($edit) {
		if (self::complete($sourcefields, $options)) {
			$result = self::getFieldPairs($sourcefields);
			
		} else {
			echo array_extract($options, array('delete'), '');
		}
	}
	
	if (is_array($result)) { // form was completed, move the new data to the old container for display
		foreach($result as $name => $value) {
			if (array_key_exists($name, $data)) $data[$name] = $value;
		}
	}
	
	if (!$edit || is_array($result)) echo self::format($sourcefields, $data, $options);
	
	// remove all display only items from the result
	if ($result) { 
		$fields = self::expandFields($sourcefields);
		foreach ($fields as $field) {
			$field = self::applyDefaults($field, $field['element'], $options);
			if (array_extract($field, array('display'), false)) {
				$name = $field['name'];
				unset($result[$name]);
			}
		}
	}

	return $result;
}

/**
 *	This formats data according to a form specified layout that would essentially match a form display without the form.
 *
 * It uses the same elements as a standard form but with a few additions for the sake of display.
 *
 * @details	supported form elements
 *		display;	false;	a boolean to indicate if this item is only presented during display within displayEdit.
 *		prefix;		;		during display; put this text before the value.
 *		suffix;		;		during display; put this text after the value.
 *
 * @param	$fields		the form definition array.
 * @param	$data		the source of the data for display.
 * @param	$options	display/form options.
 * @return	the HTML necessary to complete the display of the data.
 */
function format($fields, $data, $options=array()) {
	$cleardiv = div('style:clear: left;', '&nbsp;');
	$fields = self::expandFields($fields);
	$display = '';
	$thisdisplay = '';
	$class = '';
	foreach ($fields as $field) {
		$field = self::applyDefaults($field, $field['element'], $options);
		if (str_contains($field['name'], ':')) {
			list($composite) = explode(':', $field['name']);
			if ($field['name'] == $composite.':format') {
				$fmt = $field['value'];
			} else if ($fmt) {
				if ($data[$composite]) {
					switch ($fmt) {
						case 'datetime':
							$fmtstr = array_extract($field, array('format-string'), 'F j, Y, g:i a');
							$field['value'] = date($fmtstr, $data[$composite]);
							break;
						case 'time':
							$fmtstr = array_extract($field, array('format-string'), 'g:i a');
							$field['value'] = date($fmtstr, $data[$composite]);
							break;
						default:
					}
				} else {
					$field['value'] = '';
				}
				$fmt = '';
			} else {
				$field['value'] = '';
			}
		}
		
		if (array_key_exists('display', $field) && $field['display']) $field['class'] = 'display';
			
		if (in_array($field['element'], array('text', 'popup', 'checkbox', 'radio')) && !$field['hidden']) { 				
			if ($field['value'] == 'PARAM') {
				$field['value'] = array_extract($result, array($field['name']), array_extract($data, array($field['name']), ''));
			} else {
				if (!str_contains($field['name'], ':')) $field['value'] = array_extract($data, array($field['name']), '');
			}
			
			if (($field['value'] != '') && ($field['type'] == 'email')) $field['value'] = LINK::mailto($field['value'], '', LINK::rtn());

			switch ($field['element']) {
				case 'checkbox':
					if ($field['value']) {
						$field['value'] = $field['label'];
					} else {
						$field['value'] = $field['unchecked'];
					}
					break;
				
				case 'popup':
					$field = self::expandListValues($field);						
					if (!str_contains($field['name'], ':')) $field['value'] = array_extract($field['values'], array($field['value']), $field['default']);
					break;
					
				default:
			}
			
			
			if (!$options['suppress-empty'] || ($field['value'] != '')) {
				$lastclass = $class;
				$class = $field['class'];
				$style = $field['style'];
				
				$thisdisplay .= div('class:'.$class.' | style:'.$style, $field['prefix'].$field['value'].$field['suffix']);
			}
		} else if ($field['element'] == 'group') {
			if ($thisdisplay != '') {
				if (($display != '') && ($lastclass == 'inline') && ($class != 'inline')) {
					$display .= $cleardiv;
				}
				$display .= $thisdisplay;
				$thisdisplay = '';
			}
		}
	}
	if ($thisdisplay != '') {
		if (($display != '') && ($lastclass == 'inline')) {
			$display .= $cleardiv;
		}
		$display .= $thisdisplay;
		$thisdisplay = '';
	}
	
	if ($display != '') {
        return div('class:'.$options['class'].' | style:'.$options['style'], $display);
    } else {
        return '';
    }
}

/**
 * Determines if editting is enabled or not and allows content to be editted inline.
 *
 * @param	$name		the parameter name used to indicate if editting is enabled.
 * @param	$sourcefile	the source of the PHP code for this item.
 * @param	$content	the actual value of the content.
 * @param	$size		the width of the form element to use to edit this content.
 * @param	$data		an optional array of computed values to use when generating this content.
 * @return	the content for this item.
 */
function allowEdit($name, $sourcefile, $content, $size=80, $data=array()) {		
	$display = true;
	
	if (param('edit') == $name) {
		$lines = count(explode("\n", $content)) + 2; // add some extra
		$fields[] = array('element' => 'text', 'name' => 'edit', 'hidden');
		$fields[] = array('element' => 'text', 'name' => 'edit-content', 'size' => $size, 'rows' => $lines, 'value' => $content);
		if (FORM::complete($fields, array('submit' => 'Update '.$name))) {
			$source = FILE::read($sourcefile);
			$update = param('edit-content');
			$source = str_replace($content, $update, $source);
			FILE::write($sourcefile, $source);
			$content = $update;
			$display = true;
		} else {
			$display = false;
		}
	}
	
	if ($display) {
		eval("\$content = \"$content\";");
		echo $content;
	}
}

} // end FORM class

/**
 * Moves an uploaded file to the desired location after encrypting it.
 *
 * @param	$filename		uploaded file to encrypt.
 * @param	$destination	the name the file is supposed to take after it's been encrypted.
 * @return					a boolean indicating the success of the operation.
 */
function encrypt_uploaded_file($filename, $destination) {
	if (!file_exists($filename) || !is_file($filename) || !is_uploaded_file($filename)) return false;
	
	$result = ncrypt('e', $filename, $destination);
	@unlink($filename);
	
	return $result;
}
				
				
?>