$(document).ready(function(){

	// Slider init
	$('.influential-slider').unslider({ autoplay: true, delay: 8000, arrows:false });
	$('.unslider').addClass('row');

	// Responsive menu trigger
	$('.menu-trigger').click(function(){
		$('.menu-overlay').toggle();
		$('.vertical-sidebar').toggleClass('active');
	});

	$('.menu-overlay').click(function(){
		$(this).hide();
		$('.vertical-sidebar').removeClass('active');
	});





});
