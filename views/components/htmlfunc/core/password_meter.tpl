

<style type="text/css">

    .progress {
        height: 20px;
        width: 400px;
        margin-bottom: 20px;
        overflow: hidden;
        background-color: rgb(245, 245, 245);
        border-radius: 4px;
        box-shadow: 0px 1px 2px rgba(0, 0, 0, 0.1) inset;
    }

    .progress-bar {
        float: left;
        width: 70%;
        height: 100%;
        font-size: 12px;
        color: rgb(255, 255, 255);
        text-align: center;
        background-color: rgb(66, 139, 202);
        box-shadow: 0px -1px 0px rgba(0, 0, 0, 0.15) inset;
        transition: width 0.6s ease 0s;
    }

    .score0 { width: 0%; }
    .score1 { width: 10%; }
    .score2 { width: 25%; }
    .score3 { width: 55%; }
    .score4 { width: 85%; }
    .score5 { width: 100%; }

    .danger { background-color: rgb(217, 83, 79); }
    .warning { background-color: rgb(240, 173, 78); }
    .info { background-color: rgb(91, 192, 222); }
    .success { background-color: rgb(92, 184, 92); }

</style>

<script type="text/javascript">

    strength = new Array();
    strength[0] = "Blank";
    strength[1] = "Very Weak";
    strength[2] = "Weak";
    strength[3] = "Medium";
    strength[4] = "Strong";
    strength[5] = "Very Strong";

    function checkPassword(box) { 
        var password = box.value;

        // Get score
        var score = 0;
        if (password.length < 1) { score = 0; }
        else if (password.length < 4) { score = 1; }
        else { 
            if (password.length >= 8) { score++; }
            if (password.length >= 12) { score++; }
            if (password.match(/\d+/)) { score++; }
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) { score++; }
            if (password.match(/.[!,@,#,$,%,^,&,*,?,_,~,-,?,(,)]/)) { score++; }
            if (score == 0) { score = 1; }
        }

        // Change meter text
        document.getElementById('password_meter_text').innerHTML = strength[score];
        var meter = document.getElementById('password_meter');

        // Change meter
        if (score == 1) { meter.className = 'progress-bar score1 danger'; }
        else if (score == 2) { meter.className = 'progress-bar score2 warning'; }
        else if (score == 3) { meter.className = 'progress-bar score3 info'; }
        else if (score == 4) { meter.className = 'progress-bar score4 success'; }
        else if (score == 5) { meter.className = 'progress-bar score5 success'; }
        else { meter.className = 'progress-bar score0'; }

    }

</script>



<div class="progress">
    <div id="password_meter" class="progress-bar score0">
        <span id="password_meter_text"></span>
    </div>
</div>




