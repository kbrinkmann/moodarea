var moodarea_metabox;

jQuery(document).ready(function(){

	moodarea_metabox = new MoodareaMetabox();
});
	

function MoodareaMetabox() {

	

	//translate moodarea box above the rich text field (rich text is not inside an extra metabox)
	jQuery("#metabox-moodarea").prependTo('#post-body-content #postdivrich');
	
	
	jQuery("#metabox-moodarea .add-image").on('click', jQuery.proxy(this.addImage, this));
	jQuery("#metabox-moodarea .del-image").on('click', jQuery.proxy(this.delImage, this));
	jQuery("#metabox-moodarea .add-image-area").on('click', jQuery.proxy(this.addImageArea, this));
	jQuery("#metabox-moodarea .del-image-area").on('click', jQuery.proxy(this.delImageArea, this));
	
	//jQuery("#metabox-moodarea")
	
}


MoodareaMetabox.prototype = {

		
	addImage	: function(e) {
		
    	e.preventDefault();
    	
    	//get id to identify which moodimage will be added (first, second... )
    	var admin_mood_pic = jQuery(e.currentTarget).parents('div.picture');
    	var pic_id = String(admin_mood_pic.attr('id')).split('_');
    	pic_id = pic_id[1];
    	
    	var moodarea_media_frame;
    	
		// If the frame already exists, re-open it.
		if ( moodarea_media_frame ) {
		    moodarea_media_frame.open();
		    return;
		}		

		moodarea_media_frame = wp.media.frames.moodarea_media_frame = wp.media({
	
			className: 'media-frame moodarea-media-frame',
	        frame: 'select',
	        multiple: true,
	        title: "Wähle ein Bild als Moodbild aus",
	        library: {
	            type: 'image'
	        },
	        button: {
	            text:  "Neues Moodbild verwenden"
	        }
	        
	    });	
		
		
        moodarea_media_frame.on('select', function(){

        	
			var a = moodarea_media_frame.state().get('selection');

			
			
			var sel_images = a.map( function( attachment ) {
				attachment = attachment.toJSON();
			
				/*  special case: image for first area is exact 850 x 300 => attachment.sizes.large is null,
				 *  or image for second area is exact 423 x 300 => attachment.sizes.medium is null
				 *  
				 *  Solution: replace the medium and large url with the full from the full image 
				 */
				if( typeof(attachment.sizes.large) == "undefined" )
					attachment.sizes.large = { url: attachment.sizes.full.url };
				
				if( typeof(attachment.sizes.medium) == "undefined" )
					attachment.sizes.medium = { url: attachment.sizes.full.url };
				
				return attachment;
				
			});

			
			console.log(sel_images);
			
			
            jQuery.post(
				Moodarea.ajaxurl,
			    {
			        action		: 'moodarea-updateimage',
			        nonce		: Moodarea.nonce,
			        imgdata		: JSON.stringify(sel_images) ,
			        moodimg_id	: pic_id,
			        post_id		: jQuery('#post_ID').val()
			        
			    }, function(data, status, jqXHR) {
			    
			    	if( status != "success" && data == false ) alert("error while adding image to moodarea!")
			    }
			);
            
			
			
			admin_mood_pic.find('.add-image').addClass('hidden');
			admin_mood_pic.find('.del-image').removeClass('hidden');
			
			
			if( jQuery('.moodpicture #pic_2').hasClass('hidden') ) {
			//image added to the first area
				
				admin_mood_pic.css('background-image', 'url(' + sel_images[0].sizes.large.url + ')');
				admin_mood_pic.find('.add-image-area').removeClass('hidden');
				
			} else {
			//image added to the first second area
				
				admin_mood_pic.css('background-image', 'url(' + sel_images[0].sizes.medium.url + ')');
			
			}
			
			
			
        });

		moodarea_media_frame.open();
	},

	
	
	
	
	
	
	delImage	: function(e) {
	
		e.preventDefault();
	
		var admin_mood_pic = jQuery(e.currentTarget).parents('div.picture');
    	var pic_id = String(admin_mood_pic.attr('id')).split('_');
    	pic_id = pic_id[1];
		
		jQuery.post(
			Moodarea.ajaxurl,
		    {
		        action		: 'moodarea-updateimage',
		        nonce		: Moodarea.nonce,
		        imgdata		: { empty: null },
		        moodimg_id	: pic_id,
		        post_id		: jQuery('#post_ID').val()
		        
		    }, function(data, status, jqXHR) {
		    
		    	if( status != "success" && data == false ) alert("error while adding image to moodarea!")
		    }
		);
		
		
		admin_mood_pic.css('background-image','none');
		admin_mood_pic.find('.del-image').addClass('hidden');
		admin_mood_pic.find('.add-image').removeClass('hidden');
		
		if( !jQuery('.moodpicture #pic_2').hasClass('hidden') ) {
		//2nd image still available
		
			admin_mood_pic.find('.add-image').removeClass('hidden');
			
		} else {
		//only 1 image was available
			
			admin_mood_pic.find('.add-image-area').addClass('hidden');
			
		}
		
	},
	
	
	/**
	 * @desc deletes an image area from the DOM
	 * @return
	 */
	delImageArea	: function(e) {
		
		e.preventDefault();

		var admin_mood_pic = jQuery(e.currentTarget).parents('div.picture');
    	var pic_id = String(admin_mood_pic.attr('id')).split('_');
    	pic_id = pic_id[1];

		jQuery.post(
			Moodarea.ajaxurl,
		    {
		        action		: 'moodarea-updateimage',
		        nonce		: Moodarea.nonce,
		        moodimg_id	: 2,						//static '2' because maximal 2 image areas
		        post_id		: jQuery('#post_ID').val(),
		        modus		: 'del-image-area'
		        
		    }, function(data, status, jqXHR) {
		    
		    	if( status != "success" && data == false ) alert("error while adding image to moodarea!")

		    	
		    	//replace first image with fitting bigger image version
		    	jQuery('.moodpicture #pic_1').css('background-image', 'url(' + data + ')');

		    }
		);
    	
		admin_mood_pic.addClass('hidden');
		admin_mood_pic.css('background-image','none');
		
		jQuery('.moodpicture #pic_1 .add-image-area').removeClass('hidden');
		//alert(jQuery('.moodpicture #pic_1 .add-image-area'));
		
		jQuery('.moodpicture #pic_1').removeClass('two-pic');
		jQuery('.moodpicture #pic_2').removeClass('two-pic');

		
		

		
	},
	

	/**
	 * @desc add a second image area for moodimages to DOM
	 */
	addImageArea	: function(e) {
		
		e.preventDefault();
		
		first_moodimage = jQuery('.moodpicture #pic_1');
		first_moodimage.addClass('two-pic');
		first_moodimage.find('.add-image-area').addClass('hidden');
		

		
		
		jQuery.post(
			Moodarea.ajaxurl,
		    {
		        action		: 'moodarea-updateimage',
		        nonce		: Moodarea.nonce,
		        imgdata		: { empty: null },				//add empty image data
		        moodimg_id	: 2,							//static '2' because maximal 2 image areas
		        modus		: 'add-image-area',
		        post_id		: jQuery('#post_ID').val()
		        
		    }, function(data, status, jqXHR) {

		    	//replace first image with fitting smaller image version
		    	jQuery('.moodpicture #pic_1').css('background-image', 'url(' + data + ')');
		    	
		    	if( status != "success" && data == false ) alert("error while adding image to moodarea!")
		    }
		);
		
		jQuery('.moodpicture #pic_1').addClass('two-pic');
		jQuery('.moodpicture #pic_2').addClass('two-pic');
		
		jQuery('.moodpicture #pic_2').removeClass('hidden');
		jQuery('.moodpicture #pic_2 .del-image-area').removeClass('hidden');
		jQuery('.moodpicture #pic_2 .del-image').addClass('hidden');
		jQuery('.moodpicture #pic_2 .add-image').removeClass('hidden');
		
	}

};
