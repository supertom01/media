let input = document.getElementById("img");

$(document).ready(function () {
    $("input[type=submit]").click(function (e) {
        e.preventDefault();

        let formData = new FormData();
        formData.append("img", base64);
        formData.append("name", $("#name").val());

        let request = submit(formData);
        request.done(function (data) {
            let response = JSON.parse(data);
            if(response[0] === "SUCCESS") {
                alert("Upload Successful!");
            } else if (response[0] === "FAIL") {
                alert("Upload has failed!");
            } else if (response[0] === "SQL_DOWN") {
                alert("SQL is down!");
            } else if (response[0] === "UNAUTHORIZED") {
                alert("You do not have the permissions to perform this action!");
            }
        });
        request.fail(function (jqXHR, textStatus) {
            alert("Failed to add category. Error message: " + textStatus);
        });
    })
})