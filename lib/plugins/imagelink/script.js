jQuery(function(){
	var $container = jQuery(".plugin_linkimage");
	
	$container.each(function(idx,$element){
		console.log($element);
		$span = jQuery($element).find("div.title");
		$link = jQuery($element).find("a");
		console.log($span);
		$link.append($span);
		
	});
});