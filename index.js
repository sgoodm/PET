$(document).ready(function(){

	//init page
	$('#data_vector').click()
	$('#data_naming_x').val('x')
	$('#data_naming_y').val('y')
	$('#data_file').val('')
	$('#request_email').val('')
	$('#request_submit').prop('disabled', true)


	//vector section
	$('input[name="data_type"]').on("change", function(){
		$('#data_file').val('')

		if ( $('input[name="data_type"]:checked').val() == "raw" ){
			$('#data_naming').show()
			$("#data_naming_x , #data_naming_y").addClass("required")
			$('#data_file').prop('multiple', false)
		} else {
			$('#data_naming').hide()
			$("#data_naming_x , #data_naming_y").removeClass("required")
			$('#data_file').prop('multiple', true)
		}
	})


	//raster section
	var globals = "../DET/uploads/globals/processed"

	scanDir({ type: "scan", dir: globals }, function(options) {
		$("#raster_list").append('<option id="blank_raster_list_item" value="">Select a Raster</option>')
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

    	$('#raster_meta').show()

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
	$('input, select').on("change keyup", function(){
		validRequest()
	})

	function validRequest(){
		$('#request_submit').prop('disabled', true)

		var required =  true
		$('.required').each(function(){
			if ( $(this).val() == "" ) {
				required = false
			}
		})

		if (required == true){
			$('#request_submit').prop('disabled', false)
		}
		return required
	}

	$('#request_submit').on("click", function(){
		if (validRequest() == true){
			buildQueue()
		}
	})

	function buildQueue()

})