const password = document.getElementById("password");
const toggle = document.getElementById("togglePassword");

toggle.onclick = function(){

    if(password.type==="password"){

        password.type="text";
        toggle.innerHTML="🙈";

    }else{

        password.type="password";
        toggle.innerHTML="👁";

    }

}