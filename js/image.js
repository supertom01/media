/**
 * The HTMLElement of the preview image.
 * @type {HTMLElement}
 */
let imgDiv = document.getElementById("image");
imgDiv.style.display = "none";

/**
 * The HTMLElement of the all the elements inside a form, except the submit button.
 * @type {HTMLElement}
 */
let content = document.getElementById("content");
content.style.display = "block";

/**
 * The base64 encoding of the image.
 * @type {null}
 */
let base64 = null;

/**
 * Updates the image preview.
 * @param idImgDiv      The id of the container where the preview image lives.
 * @param idImg         The id of the preview image.
 * @param idInput       The id of the file input for the image.
 * @param aspectRatio   The aspect ratio of the image.
 */
function updateImage(idImgDiv, idImg, idInput, aspectRatio) {
    let imgDiv = document.getElementById(idImgDiv);
    let img = document.getElementById(idImg);
    let input = document.getElementById(idInput);
    crop(window.URL.createObjectURL(input.files[0]), aspectRatio).then(function (canvas) {
        img.src = canvas.toDataURL();
        base64 = canvas.toDataURL();
    });
    imgDiv.style.display = "block";
    content.style.display = "grid";
}

/**
 * Updates the file upload button with the name of the selected file.
 * @param idButton  The id of the button.
 * @param idInput   The id of the file input.
 */
function updateButton(idButton, idInput) {
    let uploadButton = document.getElementById(idButton);
    let input = document.getElementById(idInput);
    uploadButton.innerText = input.files[0].name + " selected";
}

/**
 * @param {string} url - The source image
 * @param {number} aspectRatio - The aspect ratio
 * @return {Promise<HTMLCanvasElement>} A Promise that resolves with the resulting image as a canvas element
 */
function crop(url, aspectRatio) {

    // we return a Promise that gets resolved with our canvas element
    return new Promise(resolve => {

        // this image will hold our source image data
        const inputImage = new Image();

        // we want to wait for our image to load
        inputImage.onload = () => {

            // let's store the width and height of our image
            const inputWidth = inputImage.naturalWidth;
            const inputHeight = inputImage.naturalHeight;

            // get the aspect ratio of the input image
            const inputImageAspectRatio = inputWidth / inputHeight;

            // if it's bigger than our target aspect ratio
            let outputWidth = inputWidth;
            let outputHeight = inputHeight;
            if (inputImageAspectRatio > aspectRatio) {
                outputWidth = inputHeight * aspectRatio;
            } else if (inputImageAspectRatio < aspectRatio) {
                outputHeight = inputWidth / aspectRatio;
            }

            // calculate the position to draw the image at
            const outputX = (outputWidth - inputWidth) * .5;
            const outputY = (outputHeight - inputHeight) * .5;

            // create a canvas that will present the output image
            const outputImage = document.createElement('canvas');

            // set it to the same size as the image
            outputImage.width = outputWidth;
            outputImage.height = outputHeight;

            // draw our image at position 0, 0 on the canvas
            const ctx = outputImage.getContext('2d');
            ctx.drawImage(inputImage, outputX, outputY);
            resolve(outputImage);
        };

        // start loading our image
        inputImage.src = url;
    })
}

/**
 * Submit the formData
 * @param formData  The data to send with the form.
 * @returns {jQuery|{getAllResponseHeaders: (function(): *), abort: (function(*=): jqXHR), setRequestHeader: (function(*=, *): jqXHR), readyState: number, getResponseHeader: (function(*): *), overrideMimeType: (function(*): jqXHR), statusCode: (function(*=): jqXHR)}|HTMLElement|(function(*=, *=): *)}
 */
function submit(formData) {
    return $.ajax({
        method: "POST",
        url: window.location,
        data: formData,
        processData: false,
        contentType: false
    });
}