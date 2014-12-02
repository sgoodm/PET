$(document).ready(function(){

	//init page
	$('#data_vector').click()
	$('#data_id').val('project_ID')
	$('#data_inc').val('')
	$('#data_naming_x').val('x')
	$('#data_naming_y').val('y')
	$('#data_file').val('')
	$('#request_email').val('')
	$('#request_submit').prop('disabled', true)

	var output = {	
					"request":9999, "submission":0, "completion":0, "expiration":0, "email":"", 
					"dataType":"vector", "data":1, "fileType":1, "id":"GeoID", "include":"", 
					"longitude":"longitude", "latitude":"latitude", "raster":[],
					"error":0
				}

	//vector section
	$('input[name="data_type"]').on("change", function(){
		$('#data_file').val('')

		output.dataType = $(this).val()

		if ( $('input[name="data_type"]:checked').val() == "raw" ){
			$('#data_naming').show()
			$("#data_naming_longitude , #data_naming_latitude").addClass("required")
			$('#data_file').prop('multiple', false)
		} else {
			$('#data_naming').hide()
			$("#data_naming_longitude , #data_naming_latitude").removeClass("required")
			$('#data_file').prop('multiple', true)
		}
	})


	function checkFile(){

		//determine file type
	  	var files = $("#data_file")[0]["files"];

		if (output.dataType == "raw"){
			//raw point data must be csv
			if ( files[0].name.indexOf(".csv") > -1){
	  			output.data = files[0].name
	  			output.fileType = "csv" 
	  		} else {
	  			output.error = "Raw data must be in CSV format"
	  		}
		} else {
			for (var f=0; f<files.length; f++){
				var fileName = files[f].name

				if (fileName.indexOf(".shp") > -1){ //check for shapefile
		  			output.data = fileName
		  			output.fileType = "shp"
					break
				} else if (fileName.indexOf(".geojson") > -1){ //check for geojson
		  			output.data = fileName
		  			output.fileType = "geojson"
					break
				} 
			}

			if (output.data == 1 || output.fileType == 1){
				$('#data_file').val('')
				output.error = "File type not recognized"
			}
		}
	}


	//raster section
	var globals = "../DET/uploads/globals/processed"

	scanDir({ type: "scan", dir: globals }, function(options) {
		$("#raster_list").append('<option id="blank_raster_list_item" value="">Select a Raster</option>')
	    for (var op in options){
	    	var type = options[op].substr(0,options[op].indexOf("__"))

	    	if ( !$("#optgroup_"+type).length ){
	    		$("#raster_list").append('<optgroup id="optgroup_'+type+'" label="'+type+'"></optgroup>')
	    	}
	        
	        $("#optgroup_"+type).append('<option value="' + options[op] + '">' + options[op] + '</option>')
	    	
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

	//set raster list
	var raster 
    $("#raster_list").on("change", function(){
    	$("#blank_raster_list_item").remove()

    	$('#raster_meta').show()

    	raster = $(this).val()

    	output.raster = raster

    	// console.log(raster)

    })

    //display meta
    $('#raster_list option').on("click", function(){

    	$("#raster_table_body").empty()

    	var viewRaster = $(this).val()
    	var raster_meta = readJSON(globals + "/" + viewRaster + "/meta_info.json")

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


	function confirmRequest(){
		// console.log(output)
		checkFile()

		if ( output.error != 0 ){
			alert(output.error)
		} else if ( confirm("Send request results to " + $("#request_email").val() + "?") ){

			//getQueue()
			var request
			getQueue("request", function(log){ 
				var requestLog = log.map(function(item) {
			    	return parseInt(item);
				})
				request =  requestLog.length + 1
				
			})
			// console.log(request)

			// update output object
			output.request = request
			var date = new Date()
			output.submission = Math.floor( date.getTime() / 1000 )		
			output.email = $("#request_email").val() 
			if ( $('#data_id').val() == "" ){
				output.id = "id"
			} else {
				output.id = $('#data_id').val()
			}
			output.include = $('#data_inc').val()
			if (output.dataType == "raw"){
				output.longitude = $('#data_naming_longitude').val()
				output.latitude = $('#data_naming_latitude').val()
			} 

			//uploadFiles()
	  		var files = $("#data_file")[0]["files"];
			console.log(files);
			var fileData = new FormData();
			for (var f=0; f<files.length; f++){
				var file = files[f]
				fileData.append(file.name, file);
	    	}
			fileData.append( "request", request )
			fileData.append( "type", "upload" )

			uploadFiles(fileData, function(x){})

			//sendEmail()
			var queue
			getQueue("queue", function(log){ 
				var queueLog = log.map(function(item) {
			    	return parseInt(item);
				})
				queue =  queueLog.length + 1
				
			})
			// console.log(queue)

			//buildQueue()
			buildQueue()

			var confirmHTML = "" +
		     		"Once your request has been processed an additional email will be sent containing details on how to access the data you requested. <br><br>" +
					"Current position in queue: <b>" + queue + "</b><br><br>" +
					"<b>Request Summary</b>" +
					"<br>Email: " + output.email +
					"<br>Point Data Type: " + output.dataType +
					"<br>Raster: "

			if (output.raster.length > 1){
				confirmHTML += "<p style='margin-top:0; margin-left:75px'>" 

				for (var i=0; i<output.raster.length; i++){
					confirmHTML += output.raster[i] + "<br>" 
				}

				confirmHTML += "</p>"
			} else {
				confirmHTML += output.raster[0]
			}	

			sendEmail(output.email, output.request, queue, confirmHTML)
	
			console.log(output)

		}
	}


	function uploadFiles(data, callback){
	  	$.ajax({
            type: 'post',
            url: 'process.php',
            data: data,
            cache: false,
            contentType: false,
            processData: false,
	        async: false,
	        success: function(result) {
	            callback(result)
	        }
        }).done(function(data) {
            console.log(data);
        }).fail(function(jqXHR,status, errorThrown) {
            console.log(errorThrown);
            console.log(jqXHR.responseText);
            console.log(jqXHR.status);
        });

	}

	//ajax function to read queue contents for log.csv (request #) and pending.csv (position in queue)
	function getQueue(call, callback){
	    $.ajax ({
	        url: "process.php",
	        data: { type : "read", call: call },
	        type: "post",
	        dataType: "json",
	        async: false,
	        success: function(result) {
	            callback(result)
	        }
	    })	
	}

	//ajax function to add queue to log / pending
	function buildQueue(){
		var json_output = JSON.stringify(output)
		console.log(json_output)
	    $.ajax ({
	        url: "process.php",
	        data: { type : "write", request : json_output },
	        dataType: "text",
	        type: "post",
	        async: false,
	        success: function(result) {
	            console.log(result)
	        }
	    })
	}

	// ajax function to send confirmation email
	function sendEmail(email, request, queue, message){
		
		//send confirmation email
	    $.ajax ({
	        url: "process.php",
	        data: { type : "email", email: email, request:request, queue: queue, message: message },
	        type: "post",
	        dataType: "text",
	        async: false,
	        success: function(result) {
	            console.log(result)
	    		$('#main').hide()
	    		$('#confirmation').show()
	    		$("#confirmation_text").html( "" + 
	    			"An email has been sent to <b>" + email + "</b> confirming your submission <br>" +
					"(please check your spam folder if you do not receive a confirmation email within a few minutes) <br><br>" +
					message 
				)
				
	        }
	    })	

	}


	$("#confirmation_return").click(function(){
		location.reload()
	})


})