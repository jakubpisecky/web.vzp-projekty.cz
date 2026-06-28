/*
Name: 			Landing Page - Examples
Written by: 	Okler Themes - (http://www.okler.net)
Theme Version:	3.1.0
*/

/* 
* Color Picker
*/
(function( $ ) {

	'use strict';

	$('.colorpicker-element').each(function(){
		var input = $(this).find('input');

		input.parent().colorpicker();
	});

}).apply( this, [ jQuery ]);