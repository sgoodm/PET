$(document).ready(function(){

	//vector section
	$('input[name="data_type"]').on("change", function(){
		if ( $('input[name="data_type"]:checked').val() == "raw" ){
			$('#data_naming').show()
		} else {
			$('#data_naming').hide()
		}
	})

	//raster section
	var globals = "../DET/uploads/globals/processed"

	scanDir({ type: "scan", dir: globals }, function(options) {
		$("#raster_list").append('<option id="blank_raster_list_item" value="-----">Select a Raster</option>')
	    for (var op in options){
	        $("#raster_list").append('<option value="' + options[op] + '">' + options[op] + '</option>')
	    }
    })

	function scanDir(data, callback){
		$.ajax ({
	        url: "process.php",
	        data: data,
	        dataType: "json",
	        type: "post",
	        async: false,
	        success: function(result) {
			    callback(result)
			}
	    })
	}

	var raster 
    $("#raster_list").on("change", function(){
    	$("#raster_table_body").empty()
    	$("#blank_raster_list_item").remove()

    	raster = $(this).val()
    	var raster_meta = readJSON(globals + "/" + raster + "/meta_info.json")

    	$.each(raster_meta, function(field, props){
    		$('#raster_table_body').append('<tr><td>'+field+'</td><td>'+props+'</td></tr>')
    	})

    })


	function readJSON(file) {
	    var request = $.ajax({
	    	type: "GET",
			dataType: "json",
			url: file + "?nocache=" + (new Date()).getTime(),
			async: false,
	    })
	    return request.responseJSON
	};


	//request section


})