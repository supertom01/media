let db_id;
let transmission_id;

$(document).ready(function () {
    $("#chooseFiles").hide();

    $.getJSON("all", function (data) {
        if(data[0] === "NO_DATA") {
            console.log("No category was found...");
        } else if (data[0] === "SQL_DOWN") {
            console.log("An error occurred with the sql.")
            console.log(data[1]);
        } else {
            data.forEach(category => {
                $("#category").append("<option value=\"" + category.cid +"\">" + category.name + "</option>");
            });
        }
    });

    $("#upload input[type=submit]").click(function (e) {
        e.preventDefault();

        let formData = new FormData();
        formData.append("magnet-link", $("#magnet_link").val());
        if($("#torrent_file")[0].files.length > 0) {
            formData.append("torrent-file", $("#torrent_file")[0].files[0]);
        } else {
            formData.append("torrent-file", null);
        }
        formData.append("img", base64);
        formData.append("name", $("#name").val());
        formData.append("category", $("#category").val());
        formData.append("type", $("#type").val());

        let request = submit(formData);
        request.done(function (data) {
            let response = JSON.parse(data);
            console.log(response);
            if(response[0] === "SUCCESS") {
                $("#upload").hide();
                $("#chooseFiles").show();
                db_id = response.db_id;
                transmission_id = response.transmission_id;
                response.files.forEach(file => {
                    $("#fields").append(
                        "<label class='checkboxLabel'>" +
                        "<input type='checkbox' class='files' value='" + file[0] + "'>" +
                        "<img src='img/cancel.svg' alt='Not' class='not'>" +
                        "<img src='img/check.svg' alt='Selected' class='selected'>" +
                        "<span id='name-" + file[0] + "'>" + file[1] + "</span>" +
                        "</label>" +
                        "<label class='typeLabel'>" +
                        "<div class='icon'>" +
                        "<img src='img/name.svg' alt='type'>" +
                        "</div>" +
                        "<select class='type' id='type-" + file[0] + "'>" +
                        "<option value='' selected>-- Type --</option>" +
                        "<option value='picture'>Picture</option>" +
                        "<option value='subtitle'>Subtitle</option>" +
                        "<option value='video'>Video</option>" +
                        "</select>" +
                        "</label>"
                    );
                })
            } else {
                $("form").append(response.join("<br>"));
            }
        });

        request.fail(function(jqXHR, textStatus) {
            console.log("Failed to add torrent. Error message: " + textStatus);
        });
    });

    $("#chooseFiles input[type=submit]").click(function (e) {
        e.preventDefault();

        let array = [];
        $(".files:checked").each(function () {
            let val = $(this).val();
            array.push([val, $("#name-" + val).text(), $("#type-" + val).val()]);
        });

        let formData = new FormData();
        formData.append("files", JSON.stringify(array));
        formData.append("transmission_id", transmission_id);

        $.ajax({
            url: window.location.href + "/" + db_id,
            data: formData,
            method: "POST",
            processData: false,
            contentType: false
        }).done(function (data) {
            let response = JSON.parse(data);
            if(response[0] === "SUCCESS") {
                alert("Successfully added torrent!")
            } else if (response[0] === "FAIL") {
                alert("Failed to add torrent! " + response[1]);
            } else if (response[0] === "RUNTIME_EXCEPTION") {
                alert("Failed to add torrent! " + response[1]);
            } else {
                alert("Unknown error!");
            }
        }).fail(function () {
            alert("Whoops an error!");
        });
    })
})