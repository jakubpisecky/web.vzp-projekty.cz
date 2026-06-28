/*
Name: 			InstagramFeed - Examples
Written by: 	Okler Themes - (http://www.okler.net)
Theme Version:	3.1.0
*/
(function($) {

	'use strict';
	
    $(window).on('load', function(){

        $('.instagram-feed').each(function(){
            var $this = $( this ),
                type  = $this.data('type'),
                username = 'oklerez';

            switch ( type ) {
                case 'basic':
                    var $wrapper = $this,
                        html = $wrapper.html();

                    // Instagram Feed Basic
                    $this.instagramFeed({
                        'username': username,
                        'get_data': true,
                        'callback': function(data, $this){
                            var $wrapper = $( $this ),
                                html     = $wrapper.html(),
                                images   = data.edge_owner_to_timeline_media.edges,
                                output   = '',
                                image_url = '',
                                caption = '',
                                link = '',
                                items_number = $wrapper.data('items-number');

                            // Limit items number
                            if( items_number > 12 ) {
                                items_number = 12;
                            }

                            // Set a minimum for items number
                            if( items_number < 1 ) {
                                items_number = 1;
                            }

                            // Loop trough each instagram image result
                            for( var i=0; i < items_number; i++ ) {
                                
                                if( typeof images[i] == 'undefined' ) {
                                    break;
                                }

                                // Image URL
                                if( typeof images[i].node.display_url != 'undefined' ) {
                                    image_url = images[i].node.display_url;
                                }

                                // Image Caption
                                if( typeof images[i].node.edge_media_to_caption.edges[0] != 'undefined' ) {
                                    caption = images[i].node.edge_media_to_caption.edges[0].node.text;
                                }

                                // Post Link
                                if( typeof images[i].node.shortcode != 'undefined' ) {
                                    link = images[i].node.shortcode;
                                }

                                output += '<div class="col-4 col-md-4 col-lg-6 col-xl-4 mb-3">'; 
                                    output += '<div class="image-frame image-frame-style-1 image-frame-effect-1">';
                                        output += '<span class="image-frame-wrapper">';
                                            output += '<img src="'+ image_url +'" class="img-fluid" alt="'+ caption +'"  />';
                                            output += '<span class="image-frame-action image-frame-action-effect-1 image-frame-action-sm">';
                                                output += '<a href="https://www.instagram.com/p/'+ link +'/" target="_blank" class="text-decoration-none">';
                                                    output += '<span class="image-frame-action-icon">';
                                                        output += '<i class="lnr lnr-link text-color-light"></i>';
                                                    output += '</span>';
                                                output += '</a>';
                                            output += '</span>';
                                        output += '</span>';
                                    output += '</div>';
                                output += '</div>';
                            }

                            // Build Html
                            $wrapper
                                .html('')
                                .append('<div class="row"></div>')
                                .find('.row')
                                .append(output);
                        }
                    });

                    break;

                case 'lightbox':
                    var $wrapper = $this,
                        html = $wrapper.html();

                    // Instagram Feed Basic
                    $this.instagramFeed({
                        'username': username,
                        'get_data': true,
                        'callback': function(data, $this){
                            var $wrapper = $( $this ),
                                html     = $wrapper.html(),
                                images   = data.edge_owner_to_timeline_media.edges,
                                output   = '',
                                image_url = '',
                                caption = '',
                                link = '',
                                items_number = $wrapper.data('items-number');

                            // Limit items number
                            if( items_number > 12 ) {
                                items_number = 12;
                            }

                            // Set a minimum for items number
                            if( items_number < 1 ) {
                                items_number = 1;
                            }

                            // Loop trough each instagram image result
                            for( var i=0; i < items_number; i++ ) {
                                
                                if( typeof images[i] == 'undefined' ) {
                                    break;
                                }

                                // Image URL
                                if( typeof images[i].node.display_url != 'undefined' ) {
                                    image_url = images[i].node.display_url;
                                }

                                // Image Caption
                                if( typeof images[i].node.edge_media_to_caption.edges[0] != 'undefined' ) {
                                    caption = images[i].node.edge_media_to_caption.edges[0].node.text;
                                }

                                // Post Link
                                if( typeof images[i].node.shortcode != 'undefined' ) {
                                    link = images[i].node.shortcode;
                                }

                                output += '<div class="col-4 col-md-4 col-lg-6 col-xl-4 mb-3">'; 
                                    output += '<div class="image-frame image-frame-style-1 image-frame-effect-1">';
                                        output += '<span class="image-frame-wrapper">';
                                            output += '<img src="'+ image_url +'" class="img-fluid" alt="'+ caption +'" />';
                                            output += '<span class="image-frame-action image-frame-action-effect-1 image-frame-action-sm">';
                                                output += '<a href="'+ image_url +'" class="text-decoration-none">';
                                                    output += '<span class="image-frame-action-icon">';
                                                        output += '<i class="lnr lnr-magnifier text-color-light"></i>';
                                                    output += '</span>';
                                                output += '</a>';
                                            output += '</span>';
                                        output += '</span>';
                                    output += '</div>';
                                output += '</div>';
                            }

                            // Build Html
                            $wrapper
                                .wrap('<div class="lightbox"></div>')
                                .html('')
                                .append('<div class="row"></div>')
                                .find('.row')
                                .append(output);

                            // Init Lightbox
                            $wrapper.closest('.lightbox').magnificPopup({
                                delegate: 'a',
                                type: 'image',
                                gallery: {
                                    enabled: true
                                }
                            });
                        }
                    });

                    break;

                case 'carousel':
                    var $wrapper = $this,
                        html = $wrapper.html();

                    // Instagram Feed Basic
                    $this.instagramFeed({
                        'username': username,
                        'get_data': true,
                        'callback': function(data, $this){
                            var $wrapper = $( $this ),
                                html     = $wrapper.html(),
                                images   = data.edge_owner_to_timeline_media.edges,
                                output   = '',
                                image_url = '',
                                caption = '',
                                link = '',
                                items_number = $wrapper.data('items-number');

                            // Limit items number
                            if( items_number > 12 ) {
                                items_number = 12;
                            }

                            // Set a minimum for items number
                            if( items_number < 1 ) {
                                items_number = 1;
                            }

                            // Loop trough each instagram image result
                            for( var i=0; i < items_number; i++ ) {
                                
                                if( typeof images[i] == 'undefined' ) {
                                    break;
                                }

                                // Image URL
                                if( typeof images[i].node.display_url != 'undefined' ) {
                                    image_url = images[i].node.display_url;
                                }

                                // Image Caption
                                if( typeof images[i].node.edge_media_to_caption.edges[0] != 'undefined' ) {
                                    caption = images[i].node.edge_media_to_caption.edges[0].node.text;
                                }

                                // Post Link
                                if( typeof images[i].node.shortcode != 'undefined' ) {
                                    link = images[i].node.shortcode;
                                }

                                output += '<div>';
                                    output += '<a class="text-decoration-none" target="_blank" href="https://www.instagram.com/p/'+ link +'/">';
                                        output += '<img src="'+ image_url +'" class="img-fluid" alt="'+ caption +'" />';
                                    output += '</a>';
                                output += '</div>';
                            }

                            // Build Html
                            $wrapper.append(output);

                            $wrapper.addClass('owl-carousel').owlCarousel({
                                items: 1,
                                nav: false,
                                dots: false,
                                loop: true,
                                navText: [],
                                autoplay: true,
                                autoplayTimeout: 6000,
                                rtl: ( $('html').attr('dir') == 'rtl' ) ? true : false
                            });
                        }
                    });

                    break;

                case 'nomargins':
                    var $wrapper = $this,
                        html = $wrapper.html();

                    // Instagram Feed Basic
                    $this.instagramFeed({
                        'username': username,
                        'get_data': true,
                        'callback': function(data, $this){
                            var $wrapper = $( $this ),
                                html     = $wrapper.html(),
                                images   = data.edge_owner_to_timeline_media.edges,
                                output   = '',
                                image_url = '',
                                caption = '',
                                link = '',
                                items_number = $wrapper.data('items-number');

                            // Limit items number
                            if( items_number > 12 ) {
                                items_number = 12;
                            }

                            // Set a minimum for items number
                            if( items_number < 1 ) {
                                items_number = 1;
                            }

                            // Loop trough each instagram image result
                            for( var i=0; i < items_number; i++ ) {
                                
                                if( typeof images[i] == 'undefined' ) {
                                    break;
                                }

                                // Image URL
                                if( typeof images[i].node.display_url != 'undefined' ) {
                                    image_url = images[i].node.display_url;
                                }

                                // Image Caption
                                if( typeof images[i].node.edge_media_to_caption.edges[0] != 'undefined' ) {
                                    caption = images[i].node.edge_media_to_caption.edges[0].node.text;
                                }

                                // Post Link
                                if( typeof images[i].node.shortcode != 'undefined' ) {
                                    link = images[i].node.shortcode;
                                }

                                output += '<div class="col-6 col-xl-4 px-0">';
                                    output += '<a href="https://www.instagram.com/p/'+ link +'/" class="d-flex" target="_blank" class="text-decoration-none">';
                                        output += '<div class="image-frame hover-effect-2">';
                                            output += '<div class="image-frame-wrapper">';
                                                output += '<img src="'+ image_url +'" class="img-fluid" alt="'+ caption +'" />';
                                            output += '</div>';
                                        output += '</div>';
                                    output += '</a>';
                                output += '</div>';
                            }

                            // Build Html
                            $wrapper
                                .html('')
                                .append('<div class="row mx-0"></div>')
                                .find('.row')
                                .append(output);
                        }
                    });

                    break;

            }
        });

    });

}).apply(this, [jQuery]);