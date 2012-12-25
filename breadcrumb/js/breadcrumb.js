jQuery(document).ready(function($) {
	takien_breadcrumb = function(){
	$('.takien_breadcrumb ul.children').each(function(){
		var parentwidth = $(this).parent().parent('ul').width();
		$(this).css({
			left:parentwidth
		});
	});
	$(".takien_breadcrumb li").on({
	  click: function(){
	  },
	  mouseenter: function(){
	   $(this).children('ul').stop(true,true).delay(200).slideDown();
	  },
	  mouseleave: function(){
	   $(this).children('ul').stop(true,true).delay(200).slideUp();
	  }
	});
	}
	takien_breadcrumb();
});