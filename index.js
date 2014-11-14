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
			confirmRequest()
		}
	})


	// TO DO - replicate functionality from DET tool ( see: det.js and getDir.php ) 
	function confirmRequest(){
		if(confirm("Send request results to " + output.email + "?")){
			//getQueue()
			//buildQueue()
			//ajax email
			//confirmation page
		}
	}

	function getQueue(){
		//ajax read
	}

	function buildQueue(){
		//ajax write
	}


	//=====================================================================
	//confirm and process request
	function confirmRequest_old(){
		if(confirm("Send request results to " + output.email + "?")){
			$("#message").html("Submitting Request...")
			hideShow("#content", "#confirm_loading")

			buildQueue()

			var queuePos
			getQueue("priority", function(log){ 
				var queueLog = log.map(function(item) {
			    	return parseInt(item);
				})
				if (output.priority == 1){
					queuePos = _.without(queueLog, 0).length
				} else {
					queuePos =  queueLog.length
				}
			})
			
			var confirmHTML = "" +
		     		"Once your request has been processed an additional email will be sent containing details on how to access the data you requested. <br><br>" +
					"Current position in queue: <b>" + queuePos + "</b><br><br>" +
					"<b>Request Summary</b>" +
					"<br>Country: " + output.country +
					"<br>GADM: " + output.level.substr(2) +
					"<br>Boundary Year: " + output.year +
					"<br>Data: " + output.rsub +
					"<br>Data Years: " + output.ryear +
					"<br>Include Raw Data: " + output.raw
			
			//send confirmation email
		    $.ajax ({
		        url: "getDir.php",
		        data: { type : "email", email: output.email, queue: output.queue, message: confirmHTML },
		        type: "post",
		        dataType: "text",
		        async: false,
		        success: function(result) {
		            console.log(result)
		            $("#message").html("Request has been submitted")
		    		hideShow("#confirm_loading", "#confirmation")
		    		$("#confirm_text").html( "" + 
		    			"An email has been sent to <b>" + output.email + "</b> confirming your submission <br>" +
						"(please check your spam folder if you do not receive a confirmation email within a few minutes) <br><br>" +
						confirmHTML 
					)
					
		        }
		    })				

		}
	}
	
	//ajax function to read queue contents
	function getQueue_old(call, callback){
	    $.ajax ({
	        url: "getDir.php",
	        data: { type : "read", call: call },
	        type: "post",
	        dataType: "json",
	        async: false,
	        success: function(result) {
	            callback(result)
	        }
	    })		
	}

	//ajax function to add request to queue
	function buildQueue_old(){
		var json_output = JSON.stringify(output)
	    $.ajax ({
	        url: "getDir.php",
	        data: { type : "write", action : json_output },
	        dataType: "text",
	        type: "post",
	        async: false,
	        success: function(result) {
	            console.log(result)
	        }
	    })
	}

	function hideShow(hide, show){
		$(hide).hide()
		$(show).show()
	}
	//=====================================================================


})