/**
 * AJAxify a single gallery post
 *
 * @uses backbone.js and underscore.js
 */

if (typeof naked_gallery === 'undefined') {
	// on non-gallery posts naked_gallery will be undefined so we define it
	// here to make sure no errors are thrown 
	var naked_gallery = {};
}

// document ready + closure
jQuery(function($) {

	updateGlobalData = function(data)
	{
		// update the naked_gallery object data
		naked_gallery.post_id 		= data.post.id;
		naked_gallery.current_page 	= data.page;
		naked_gallery.numpages 		= data.numpages;
		naked_gallery.next_json 	= data.next_json;
		naked_gallery.prev_json 	= data.prev_json;
		naked_gallery.next_link 	= data.next_link;
		naked_gallery.prev_link 	= data.prev_link;
	}

	/****************************************************************
	 * The Model
	 */
	window.Slide = Backbone.Model.extend({
		
		initialize: function(data)
		{
			// console.log( data );

			updateGlobalData( data );
		},

		parse: function(data)
		{
			data.mediaHTML 	= data.media.html;
			data.descHTML 	= data.post.content;

			return data;
		}
	});


	/****************************************************************
	 * The Collection
	 */
	window.Gallery = Backbone.Collection.extend({
		model 	: Slide,
		url		: ''// will be set when the prev/next button is clicked


	});


	/****************************************************************
	 * The Views
	 */

	 /**
	  * The View that displays the primary content (usually an image or video)
	  */
	 window.PrimaryView = Backbone.View.extend({
	 	template 	: '#gallery-primary-tpl',
	 	tagName 	: 'div',
	 	className   : 'media-wrapper',

 		initialize: function() 
	    {
	    	this.initializeTemplate();
	    },
	    

	    initializeTemplate: function() 
		{
			this.template = _.template($(this.template).html());
		},


		render: function() 
	    {
	      	var renderedContent = this.template(this.model.toJSON());
	      	$(this.el).html(renderedContent);

	      	// return itself to allow you to chain other calls to render
	      	return this;
	    }

	 });


	 /**
	  * The View that displays the secondary content (usually a description and tags)
	  */
	 window.SidebarView = Backbone.View.extend({
	 	template 	: '#gallery-sidebar-tpl',
	 	tagName 	: 'div',
	 	className 	: 'description',
	 	
	 	initialize: function() 
	    {
	      	this.initializeTemplate();
	    },


	    initializeTemplate: function() 
		{
			this.template = _.template($(this.template).html());
		},


		render: function() 
	    {
	      	var renderedContent = this.template(this.model.toJSON());
	      	$(this.el).html(renderedContent);

		    // return itself to allow you to chain other calls to render
		    return this;
	    }

	 });

	 window.GalleryView = Backbone.View.extend({

	 	nextClicks : 0, // tracks how many times the next button has been clicked
	 	prevClicks : 0, // tracks how many times the prev button has been clicked

	 	events: {
	 		'click .next' : 'next',
	 		'click .prev' : 'prev'
	 	},

	 	initialize: function()
	 	{
	 		// bindAll is used to permanently associate methods (callbacks) with a specific object
	  		_.bindAll(this, 'render');

	  		this.collection.bind('reset', this.render);
	 	},


	 	render: function()
	 	{
	 		var $container = this.$el,
	 			collection = this.collection;

	 		// The iterator (each) is called with three arguments: (element, index, list)
	 		// See http://underscorejs.org/#each for more details
	 		//
	 		// @note Although an iterator is used here, this collection should actually only have
	 		// one item in it.
	     	collection.each(function( model ) {

	     		var primary = new PrimaryView({
	     				model: model,
	     				collection: collection
	     			});
	     		
	     		var sidebar = new SidebarView({
	     				model : model,
	     				collection: collection
	     			});

	     		var primary 	= primary.render().el,
	     			sidebar 	= sidebar.render().el;

	     		galleryView.placePrimary( primary, model.get('media') );

	     		galleryView.updateHistory( model );

	     		// update the description
	     		galleryView.updateDesc( model );
	     	});

	     	// update the nav
	     	galleryView.updateNav();
	 	},


	 	/**
	 	 * replace existing media-container with new one
	 	 *
	 	 * @param el (text) the html of the element to be inserted
	 	 * @param media (obj) the media data object
	 	 */ 
	 	placePrimary: function(el, media)
	 	{

     		var $el 		= $(el),
     			$wrapper 	= $('.media-container'),
     			pos 		= $wrapper.position(),
     			wrapper_w 	= $wrapper.width();

     		$el.css({ 
     			display: 'none', 
     			position:'absolute', 
     			left:pos.left, 
     			top:pos.top,
     			width: wrapper_w
     		});

     		$wrapper.find('.media-wrapper').fadeOut( 'fast', function(){
     			$(this).remove();
     		});

     		if( 'image' == media.type ) {

     			// get width and calculate height...
     			// the width is set by comparing the wrapper width and  
     			// the media width and using the smaller one because the
     			// css for the image is such that it will never be larger 
     			// than the width of the wrapper
     			w = wrapper_w > media.width ? media.width : wrapper_w;
     			ratio = media.height / media.width;
     			
     			// console.log( w, ratio );

	     		$wrapper.animate({
	     			height: ( w * ratio )
	     		}, '6000' ).append( el );
	     	}
	     	// if an embed then we need to calculate the new after fitVids height
	     	// before we append the el and apply fitVids
	     	else if( 'embed' == media.type ) {
	     		// figure out the aspect ratio
	     		h = $el.children().attr('height');
	     		w = $el.children().attr('width');
	     		ratio = h/w;

	     		new_h = $wrapper.width() * ratio;

	     		// append el and apply fitVids
	     		$wrapper.animate({
	     			height: new_h + 20 // add 20 for padding
	     		}, '6000' ).append( el ).fitVids();
	     	}

     		$el.fadeIn();
	 	},


	 	next: function(ev)
	 	{
	 		if( '#' != naked_gallery.next_json 
	 			&& naked_gallery.current_page != naked_gallery.numpages
	 			&& ( '-1' == naked_gallery.refresh_threshold
	 				 || galleryView.nextClicks < naked_gallery.refresh_threshold ) ) {

	 			ev.preventDefault();

		 		// set the naked_gallery back_url variable
		 		naked_gallery.back_url = window.location.href;

		 		// update click counts
		 		galleryView.nextClicks += 1
		 		galleryView.prevClicks -= 1

				this.collection.url = naked_gallery.next_json;

				this.collection.fetch();
	 		}
	 	},


	 	prev: function(ev)
	 	{
	 		if( '#' != naked_gallery.prev_json 
	 			&& naked_gallery.current_page != 1
	 			&& ( '-1' == naked_gallery.refresh_threshold
	 				 || galleryView.prevClicks < naked_gallery.refresh_threshold ) ) {

	 			ev.preventDefault();

		 		// set the naked_gallery back_url variable
		 		naked_gallery.back_url = window.location.href;

		 		// update click counts
		 		galleryView.nextClicks -= 1
		 		galleryView.prevClicks += 1

				this.collection.url = naked_gallery.prev_json;

				this.collection.fetch();
			}
	 	},


	 	updateHistory: function(model)
	 	{
	 		// change the url in the browser bar
	 		try {
	 			var url = model.get( 'post' ).url + naked_gallery['current_page'] + '/';
	 			window.history.pushState( {}, document.title, url );
	 		} catch ( e ) { console.log( e ); }

	 		// react to the browser back button
	 		try {
		 		window.addEventListener("popstate", function(e) {
		 			window.location = naked_gallery['back_url'];
		 		});
	 		} catch ( e ) { console.log( e ); }
	 	},


	 	updateNav: function()
	 	{
	 		$nav = $('#gallery-nav');
	 		$nav.find('.current').text( String(naked_gallery['current_page']) );
	 		$nav.find('.numpages').text( String(naked_gallery['numpages']) ); 

	 		// update the next/prev links
	 		var next_link = String(naked_gallery['next_link']),
	 			prev_link = String(naked_gallery['prev_link']);
	 		
	 		$next = $nav.find('.button.next');
	 		$prev = $nav.find('.button.prev')
	 		
	 		$next.attr('href', next_link);
	 		$prev.attr('href', prev_link);

	 		if( '#' == next_link || '#' == prev_link ) {
	 			if( '#' == next_link ) $next.addClass('disabled');
	 			if( '#' == prev_link ) $prev.addClass('disabled');
	 		}
	 		else {
	 			// remove any disabled classes from the nav buttons
	 			$next.removeClass('disabled');
	 			$prev.removeClass('disabled');
	 		}
	 	},


	 	updateDesc: function(model)
	 	{
	 		$desc = $('#slide .description');
	 		$desc.html( model.get('descHTML') ); 
	 	}
	});

	if( false == $.isEmptyObject( naked_gallery ) ) {

		// Create a new Gallery instance
		window.gallery = new Gallery();

		// Create the view
		window.galleryView = new GalleryView({
			collection: gallery,
			el: $('#slide')
		});

	}

});