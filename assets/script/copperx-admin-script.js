document.addEventListener("DOMContentLoaded", function(){
    const enableTest = document.getElementsByClassName("copperx-test-mode-switch")[0];
    const testInputLabel = document.getElementsByClassName("copperx-test-secret-key-label")[0];
    const testInputField = document.getElementsByClassName("copperx-test-secret-key-text-field")[0];

    if(enableTest && testInputLabel && testInputField){
        if(enableTest.checked){
            testInputLabel.style.display = "inline-block";
            testInputField.style.display = "inline-block";
        }

        enableTest.addEventListener("change", (event) => {
            if(event.target.checked){
                testInputLabel.style.display = "inline-block";
                testInputField.style.display = "inline-block";
            }else{
                testInputLabel.style.display = "none";
                testInputField.style.display = "none";
            }
        })
    }
})

