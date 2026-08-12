document.addEventListener('DOMContentLoaded', function () {

    const department = document.getElementById('department');
    const position = document.getElementById('position');

    function filterPositions() {

        const selectedDepartment = department.value;

        position.selectedIndex = 0;

        Array.from(position.options).forEach(option => {

            if (option.value === "") {
                return;
            }

            option.hidden =
                option.dataset.department !== selectedDepartment;

        });

    }

    department.addEventListener('change', filterPositions);

    filterPositions();

});

function copyApplicationLink(path) {

    const fullUrl =
        window.location.origin + path;

    navigator.clipboard.writeText(fullUrl)
        .then(() => {

            alert('Application link copied!');

        })
        .catch(() => {

            prompt(
                'Copy this application link:',
                fullUrl
            );

        });
}