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