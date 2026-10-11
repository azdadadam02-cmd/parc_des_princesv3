const signUpBtn = document.getElementById('signUpBtn');
const signInBtn = document.getElementById('signInBtn');
const mainContainer = document.getElementById('mainContainer');

signUpBtn.addEventListener('click', () => {
    mainContainer.classList.add("right-panel-active");
});

signInBtn.addEventListener('click', () => {
    mainContainer.classList.remove("right-panel-active");
});
