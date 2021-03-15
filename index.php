<!DOCTYPE html>
<html lang="en" style="height: 100%;">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Angular Generator v0.2 | Classic version</title>
    <script src="./scripts/jquery-3.5.1.js" integrity="sha256-QWo7LDvxbWT2tbbQ97B53yJnYU3WhH/C8ycbRAkjPDc=" crossorigin="anonymous"></script>

    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="./styles/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

    <!-- Optional theme -->
    <link rel="stylesheet" href="./styles/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

    <!-- Latest compiled and minified JavaScript -->
    <script src="./scripts/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>

    <script src="./scripts/sweetalert-2.10.js"></script>

</head>

<body style="height: 100%;">

    <div class="container well well-sm" style="overflow: hidden; height: 100%">

        <div class="row">
            <form action="" method="post" class="col-lg-8" id="from-url-form">
                <!-- <div class="form-inline">
                    <label for="inputEmail4">Your URL</label>
                    <input required type="url" class="form-control" name="endpoint" value=''>

                    <label for="inputPassword4">Class name</label>
                    <input ut type="text" class="form-control" name="classname" value=''>

                    <button class="btn btn-default" type="submit" id="from-url-submit-button">Go!</button>
                </div> -->
            </form>
        </div>

        <div class="row" style="overflow-y:auto; height: 100%">
            <div class="col-lg-6 h-100" style="height: 100%">
                    <span id="http_code" class="label label-success"></span>
                    <span id="total_time" class="label label-primary"></span>
                    <span id="primary_ip" class="label label-default"></span>
                    <span id="source" class="label label-success"></span>
                    <div style="height: 98%">
                        <form method="post" id="from-json-form" style="height: 98%">
                            <div class='form-inline' style='margin-bottom: 10px; margin-top: 5px'>
                                <label for="inputPassword4">Class name</label>
                                <input ut type="text" class="form-control" name="classname" value=''>
                                <button class="btn btn-default" type="submit" id="from-json-submit-button">Go!</button>
                            </div>
                            <textarea name="json_data" id="from-json-form-textarea" cols="30" rows="25" class="form-control" style="cursor: text; resize: none; height: 98%"></textarea>
                            
                        </form>
                    </div>
                </div>

                <div class="col-lg-6 mb-5" style="overflow-y:auto; height: 100%;">
                    <span class="label label-success">TypeScript Code</span>
                    <span class="label label-warning">Click on code block to copy the content</span>
                    <div style="margin-bottom: 70px;" id="typescript-code-container">
                    </div>
                </div>
            </div>
        </div>

        <script>

            $(document).ready(()=>{
                $('#from-json-submit-button').on('click', (event)=>{
                    event.preventDefault();
                    formData = new FormData($('#from-json-form')[0]);

                    getAndFeed(formData);
                })
            })

            $(document).ready(()=>{
                $('#from-url-submit-button').on('click', (event)=>{
                    event.preventDefault();
                    formData = new FormData($('#from-url-form')[0]);

                    getAndFeed(formData, true);
                })
            })

            function getAndFeed(formData, isFromUrl = false) {
                $.ajax({
                        method: "POST",
                        enctype: "multipart/form-data",
                        processData: false,
                        contentType: false,
                        data: formData,
                        url: "./lib/engine.php",
                        success: (result)=>{
                            // console.log(result)
                            $("#typescript-code-container").html(result.typescript_code)
                            $('#from-json-form-textarea').val(result.json)

                            if(isFromUrl) {
                                // $('#http_code').text("HTTP code: "+result.http_code)
                                // $('#total_time').text("Time: "+result.total_time+" seconds")
                                // $('#primary_ip').text(result.primary_ip+":"+result.primary_port)
                                $('#source').text("Source: From Url")
                            } else {
                                $('#source').text("Source: From JSON form")
                            }

                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                timer: 2000,
                                timerProgressBar: true,
                                title: 'Success',
                                text: "Code genereated successfully!",
                                icon: 'success',
                                showConfirmButton: false,
                                // confirmButtonText: 'Cool'
                                didOpen: (toast) => {
                                    toast.addEventListener('mouseenter', Swal.stopTimer)
                                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                                }
                            });
                        },
                        error: (error)=>{
                            // console.log(error)
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                timer: 2000,
                                timerProgressBar: true,
                                title: 'Error['+ error.status +']',
                                text: error.statusText,
                                icon: 'error',
                                showConfirmButton: false,
                                // confirmButtonText: 'Cool'
                                didOpen: (toast) => {
                                    toast.addEventListener('mouseenter', Swal.stopTimer)
                                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                                }
                            });
                        }
                    })
            }

            $(document).on('click', 'pre', (event) => {
                textarea = $('<textarea style=" opacity: 0; position: absolute; top: 0px ;height: 0px; width: 0px;"></textarea>');
                $('body').append(textarea);
                code = $(event.currentTarget);
                textarea.val(code.text());
                textarea.select();
                document.execCommand('copy');

                code.css('background-color', '#ddffdd');
                code.css('color', 'black');

                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    timer: 2000,
                    timerProgressBar: true,
                    title: 'Copied!',
                    text: 'You can now paste the code in the coresponding area of your project!',
                    icon: 'success',
                    showConfirmButton: false,
                    // confirmButtonText: 'Cool'
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer)
                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                    }
                })
            });
        </script>
    </div>
</body>

</html>