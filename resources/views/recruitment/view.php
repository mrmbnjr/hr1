/* ==========================================================
   RAM-YUM Recruitment Management
========================================================== */

.main-content{
    margin-left:var(--sidebar-width);
    min-height:100vh;
    background:#f8f3eb;
}

@media(max-width:960px){
    .main-content{
        margin-left:0;
    }
}

/* ==========================================================
   HERO
========================================================== */

.recruitment-hero{

    margin:28px;

    background:linear-gradient(
        135deg,
        var(--ry-maroon),
        var(--ry-maroon-dark)
    );

    color:#fff;

    border:3px solid var(--ry-gold);

    border-radius:22px;

    padding:32px;

    display:flex;
    justify-content:space-between;
    align-items:center;

    box-shadow:0 14px 30px rgba(0,0,0,.12);

}

.hero-tag{

    display:inline-flex;

    padding:8px 16px;

    border-radius:999px;

    background:rgba(255,255,255,.12);

    border:1px solid rgba(255,255,255,.15);

    font-size:13px;

}

.recruitment-hero h1{

    margin:14px 0 8px;

    font-family:"Baloo 2",sans-serif;

    font-size:34px;

}

.recruitment-hero p{

    color:rgba(255,255,255,.82);

    max-width:580px;

}

/* ==========================================================
   BUTTONS
========================================================== */

.btn-primary{
    border:none;
    padding:14px 22px;
    border-radius:14px;
    background:var(--ry-gold);
    color:var(--ry-maroon-dark);
    font-family:"Baloo 2",sans-serif;
    font-size:15px;
    font-weight:700;
    display:flex;
    align-items:center;
    gap:10px;
    cursor:pointer;
    transition:.2s;
    text-decoration: none;
}

.btn-primary:hover{

    transform:translateY(-3px);

    background:#ffd56f;

}

.btn-outline{

    display:inline-flex;

    align-items:center;

    justify-content:center;

    gap:8px;

    padding:10px 18px;

    border-radius:12px;

    border:2px solid var(--ry-gold);

    background:#fff;

    color:var(--ry-maroon-dark);

    text-decoration:none;

    font-family:"Baloo 2",sans-serif;

    transition:.2s;

}

.btn-outline:hover{

    background:var(--ry-gold);

}

.btn-danger{

    border:none;

    border-radius:12px;

    padding:10px 18px;

    background:#e0362c;

    color:#fff;

    font-family:"Baloo 2",sans-serif;

    cursor:pointer;

    transition:.2s;

}

.btn-danger:hover{

    background:#b51d16;

}

/* ==========================================================
   STATS
========================================================== */

.recruitment-stats{

    margin:0 28px 28px;

    display:grid;

    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));

    gap:20px;

}

.mini-card{

    background:#fff;

    border-radius:18px;

    border:2px solid #eddcc2;

    padding:22px;

    display:flex;

    align-items:center;

    gap:18px;

    transition:.2s;

}

.mini-card:hover{

    transform:translateY(-5px);

    border-color:var(--ry-gold);

}

.mini-card i{

    width:58px;
    height:58px;

    border-radius:16px;

    background:linear-gradient(
        180deg,
        var(--ry-maroon),
        var(--ry-maroon-dark)
    );

    display:flex;
    align-items:center;
    justify-content:center;

    color:var(--ry-gold);

    font-size:22px;

}

.mini-card h2{

    margin:0;

    font-family:"Baloo 2",sans-serif;

    color:var(--ry-maroon-dark);

}

.mini-card span{

    color:#84726b;

}

/* ==========================================================
   FILTERS
========================================================== */

.filter-card{
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));
    gap:18px;
    margin:0 28px 28px;
    background:#fff;
    border:2px solid #eddcc2;
    border-radius:20px;
    padding:22px;
}

.filter-group{
    display:grid;
    grid-template-columns:repeat(4, 1fr);
    gap:18px;
}

.filter-group select{

    width:100%;

    height:48px;

    border:2px solid #ecd8bc;

    border-radius:14px;

    padding:0 16px;

    background:#fffdf8;

    font-family:"Nunito",sans-serif;

    transition:.2s;

}

.filter-group select:focus{

    outline:none;

    border-color:var(--ry-gold);

}

/* ==========================================================
   JOB GRID
========================================================== */

.job-grid{

    margin:0 28px 28px;

    display:grid;

    grid-template-columns:repeat(auto-fill,minmax(420px,1fr));

    gap:24px;

}

.job-card{

    background:#fff;

    border:2px solid #eddcc2;

    border-radius:22px;

    padding:24px;

    transition:.2s;

    box-shadow:0 8px 18px rgba(0,0,0,.05);

}

.job-card:hover{

    transform:translateY(-5px);

    border-color:var(--ry-gold);

}

.job-header{

    display:flex;

    justify-content:space-between;

    gap:20px;

    margin-bottom:22px;

}

.job-header h2{

    margin:0;

    color:var(--ry-maroon-dark);

    font-family:"Baloo 2",sans-serif;

}

.job-header p{

    margin-top:4px;

    color:#88756d;

}

/* ==========================================================
   STATUS
========================================================== */

.status{

    align-self:flex-start;

    padding:8px 14px;

    border-radius:999px;

    font-size:12px;

    font-weight:700;

}

.status.open{

    background:#daf6df;

    color:#239b56;

}

.status.closed{

    background:#ffe0de;

    color:#b71c1c;

}

/* ==========================================================
   META
========================================================== */

.job-meta{

    display:grid;

    grid-template-columns:repeat(3,1fr);

    gap:18px;

    margin-bottom:24px;

}

.job-meta strong{

    display:block;

    margin-bottom:6px;

    color:var(--ry-maroon-dark);

    font-size:13px;

}

.job-meta span{

    color:#766760;

    font-size:14px;

}

/* ==========================================================
   PIPELINE
========================================================== */

.pipeline{

    display:grid;

    grid-template-columns:repeat(4,1fr);

    gap:14px;

    margin-bottom:24px;

}

.pipeline div{

    background:#fff7ea;

    border-radius:14px;

    text-align:center;

    padding:18px 10px;

}

.pipeline h3{

    margin:0;

    font-family:"Baloo 2",sans-serif;

    color:var(--ry-maroon-dark);

    font-size:24px;

}

.pipeline span{

    font-size:12px;

    color:#8a776f;

}

/* ==========================================================
   ACTIONS
========================================================== */

.job-actions{

    display:flex;

    gap:12px;

    flex-wrap:wrap;

}

/* ==========================================================
   RESPONSIVE
========================================================== */

@media(max-width:960px){

.recruitment-hero{

flex-direction:column;

align-items:flex-start;

gap:24px;

}

.filter-group{

grid-template-columns:1fr;

}

.job-grid{

grid-template-columns:1fr;

}

.job-meta{

grid-template-columns:1fr;

}

.pipeline{

grid-template-columns:repeat(2,1fr);

}

}

@media(max-width:600px){

.job-actions{

flex-direction:column;

}

.btn-outline,
.btn-danger,
.btn-primary{

width:100%;

justify-content:center;

}

}

/* ==========================================================
   RAM-YUM HRM
   Create Job Posting
========================================================== */

.main-content{
    margin-left:var(--sidebar-width);
    min-height:100vh;
    background:var(--bg);
    color:var(--ink);
}

@media (max-width:960px){
    .main-content{
        margin-left:0;
    }
}

/* ==========================================================
   PAGE HEADER
========================================================== */

.page-header{

    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:20px;

    margin:28px;
    margin-bottom:22px;

    padding:28px 32px;

    background:linear-gradient(
        135deg,
        var(--maroon),
        var(--maroon-dark)
    );

    border:3px solid var(--gold);

    border-radius:var(--radius-card);

    color:var(--white);

    box-shadow:var(--shadow-soft);

}

.page-header h1{

    margin:0;

    font-family:var(--font-heading);

    font-size:34px;

}

.page-header p{

    margin-top:8px;

    color:rgba(255,255,255,.82);

}

/* ==========================================================
   FORM CARD
========================================================== */

.form-card{

    margin:0 28px 32px;

    padding:34px;

    background:var(--card-bg);

    border:2px solid var(--card-border);

    border-radius:var(--radius-card);

    box-shadow:var(--shadow-soft);

}

/* ==========================================================
   GRID
========================================================== */

.form-grid{

    display:grid;

    grid-template-columns:repeat(2,1fr);

    gap:24px;

    margin-bottom:26px;

}

/* ==========================================================
   FORM GROUP
========================================================== */

.form-group{

    display:flex;

    flex-direction:column;

    gap:10px;

    margin-bottom:24px;

}

.form-group label{

    font-family:var(--font-heading);

    font-size:15px;

    font-weight:700;

    color:var(--maroon-dark);

}

/* ==========================================================
   INPUTS
========================================================== */

.form-group input,
.form-group select,
.form-group textarea{

    width:100%;

    padding:14px 16px;

    border:2px solid var(--pink-border);

    border-radius:var(--radius-field);

    background:var(--white);

    color:var(--ink);

    font-family:var(--font-body);

    font-size:15px;

    transition:var(--transition);

}

.form-group textarea{

    resize:vertical;

    min-height:150px;

    line-height:1.65;

}

.form-group input::placeholder,
.form-group textarea::placeholder{

    color:var(--ink-soft);

}

/* ==========================================================
   FOCUS
========================================================== */

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus{

    outline:none;

    border-color:var(--gold);

    box-shadow:
        0 0 0 4px rgba(242,193,78,.22);

}

/* ==========================================================
   SELECT
========================================================== */

.form-group select{

    cursor:pointer;

    appearance:none;

    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' fill='none' stroke='%236f1414' stroke-width='2'%3E%3Cpath d='M5 7l5 6 5-6'/%3E%3C/svg%3E");

    background-repeat:no-repeat;

    background-position:right 15px center;

    background-size:18px;

    padding-right:46px;

}

/* ==========================================================
   BUTTONS
========================================================== */

.btn-primary{

    display:inline-flex;

    align-items:center;

    justify-content:center;

    gap:10px;

    padding:14px 24px;

    border:none;

    border-radius:var(--radius-btn);

    background:var(--gold);

    color:var(--maroon-dark);

    font-family:var(--font-heading);

    font-size:15px;

    font-weight:700;

    cursor:pointer;

    transition:var(--transition);

}

.btn-primary:hover{

    transform:translateY(-3px);

    background:var(--gold-dark);

    color:var(--white);

}

.btn-outline{

    display:inline-flex;

    align-items:center;

    justify-content:center;

    gap:10px;

    padding:14px 24px;

    border:2px solid var(--gold);

    border-radius:var(--radius-btn);

    background:transparent;

    color:var(--maroon-dark);

    text-decoration:none;

    font-family:var(--font-heading);

    font-weight:700;

    transition:var(--transition);

}

.btn-outline:hover{

    background:var(--gold);

    color:var(--maroon-dark);

}

/* ==========================================================
   ACTIONS
========================================================== */

.form-actions{

    display:flex;

    justify-content:flex-end;

    gap:16px;

    padding-top:28px;

    margin-top:10px;

    border-top:2px dashed rgba(111,20,20,.15);

}

/* ==========================================================
   VALIDATION
========================================================== */

.form-group input:invalid:focus,
.form-group select:invalid:focus,
.form-group textarea:invalid:focus{

    border-color:var(--red);

    box-shadow:0 0 0 4px rgba(224,54,44,.18);

}

.form-group.error input,
.form-group.error select,
.form-group.error textarea{

    border-color:var(--red);

}

.form-group small{

    margin-top:2px;

    font-size:13px;

    color:var(--red);

    font-family:var(--font-body);

}

/* ==========================================================
   DISABLED
========================================================== */

.form-group input:disabled,
.form-group select:disabled,
.form-group textarea:disabled{

    background:#ece6dc;

    cursor:not-allowed;

    opacity:.75;

}

.btn-primary:disabled,
.btn-outline:disabled{

    opacity:.6;

    cursor:not-allowed;

    transform:none;

}

/* ==========================================================
   HOVER EFFECTS
========================================================== */

.form-card{

    transition:var(--transition);

}

.form-card:hover{

    box-shadow:
        0 16px 36px rgba(74,15,15,.18);

}

.form-group input:hover,
.form-group select:hover,
.form-group textarea:hover{

    border-color:var(--gold);

}

/* ==========================================================
   AUTOFILL
========================================================== */

input:-webkit-autofill,
textarea:-webkit-autofill,
select:-webkit-autofill{

    -webkit-box-shadow:0 0 0 1000px var(--white) inset;

    -webkit-text-fill-color:var(--ink);

}

/* ==========================================================
   TEXTAREA
========================================================== */

textarea{

    scrollbar-width:thin;

    scrollbar-color:var(--gold) transparent;

}

textarea::-webkit-scrollbar{

    width:8px;

}

textarea::-webkit-scrollbar-thumb{

    background:var(--gold);

    border-radius:10px;

}

/* ==========================================================
   ANIMATIONS
========================================================== */

.page-header,
.form-card{

    animation:fadeUp .45s ease;

}

@keyframes fadeUp{

    from{

        opacity:0;

        transform:translateY(18px);

    }

    to{

        opacity:1;

        transform:none;

    }

}

/* ==========================================================
   RESPONSIVE
========================================================== */

@media (max-width:1100px){

    .form-grid{

        grid-template-columns:1fr;

    }

}

@media (max-width:768px){

    .page-header{

        flex-direction:column;

        align-items:flex-start;

        text-align:left;

        padding:24px;

    }

    .page-header h1{

        font-size:28px;

    }

    .form-card{

        margin:20px;

        padding:24px;

    }

    .form-actions{

        flex-direction:column-reverse;

    }

    .btn-primary,
    .btn-outline{

        width:100%;

    }

}

@media (max-width:480px){

    .page-header{

        margin:16px;

    }

    .form-card{

        margin:16px;

        padding:18px;

    }

    .page-header h1{

        font-size:24px;

    }

    .form-group label{

        font-size:14px;

    }

    .form-group input,
    .form-group select,
    .form-group textarea{

        font-size:14px;

        padding:12px 14px;

    }

}

/* ==========================================================
   DARK MODE
========================================================== */

[data-theme="dark"] .form-card{

    background:var(--card-bg);

    border-color:var(--card-border);

}

[data-theme="dark"] .form-group label{

    color:var(--ink);

}

[data-theme="dark"] .form-group input,
[data-theme="dark"] .form-group select,
[data-theme="dark"] .form-group textarea{

    background:#241511;

    border-color:#5a3934;

    color:var(--ink);

}

[data-theme="dark"] .form-group input::placeholder,
[data-theme="dark"] .form-group textarea::placeholder{

    color:var(--ink-soft);

}

[data-theme="dark"] .btn-outline{

    color:var(--gold);

    border-color:var(--gold);

}

[data-theme="dark"] .btn-outline:hover{

    background:var(--gold);

    color:var(--maroon-dark);

}

[data-theme="dark"] .form-actions{

    border-top:2px dashed rgba(242,193,78,.18);

}

[data-theme="dark"] input:-webkit-autofill,
[data-theme="dark"] textarea:-webkit-autofill,
[data-theme="dark"] select:-webkit-autofill{

    -webkit-box-shadow:0 0 0 1000px #241511 inset;

    -webkit-text-fill-color:var(--ink);

}