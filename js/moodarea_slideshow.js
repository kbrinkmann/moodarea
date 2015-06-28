
/**
 * @desc realize a quick slideshow of image urls (config.images = []) 
 * add <img> tag with css_class = config.css_class and blend between them
 */
function MoodareaSlideshow(config) {


	if( !jQuery.isPlainObject(config) || !jQuery.isArray(config.images) || typeof(config.css_class) == "undefined")
		return;
	
	
	
	this.config = config;
	this.slide_switchtime = 5000;
	this.slide_blendtime = 800;
	
	this.loaded_images = 0;
	
	
	var imgobj = new Array();
      
	visible_first_image = jQuery('.' + this.config.css_class);
	
	for(i in this.config.images) {

		//preload given image urls in an <img>-dom-object, after that start slideshow
		imgobj[i] = jQuery('<img/>');
		imgobj[i]
			.addClass( visible_first_image.attr('class') )
		    .attr('src', this.config.images[i])
			.on('load', jQuery.proxy(function() { 
			
				jQuery('.' + this.config.css_class).parent().append(imgobj);
				
				if(++this.loaded_images == this.config.images.length)	//all images are loaded
					this.startSlideshow(); 
			
			}, this) );
        
    
	};
	
	
	
	
}

MoodareaSlideshow.prototype = {
		
	startSlideshow :	function() {
	
		window.setTimeout( jQuery.proxy( this.showSlide, this) , this.slide_switchtime);
		this.slide_index = 0;
	
	},
	
	
	showSlide :			function() {
		
		jQuery('.' + this.config.css_class).eq( this.slide_index ).fadeOut(  this.slide_blendtime ,'swing');
		if( this.slide_index + 1 > this.loaded_images ) this.slide_index = 0; else this.slide_index++;
		jQuery('.' + this.config.css_class).eq( this.slide_index ).fadeIn( this.slide_blendtime ,'swing', jQuery.proxy( this.afterSlideFade, this));
		
	},
	
	
	afterSlideFade :	function(e) {
		
		window.setTimeout( jQuery.proxy( this.showSlide, this) , this.slide_switchtime);
	}
		
};