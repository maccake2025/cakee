function bindStarsInput(selector = '.review-stars-input') {
    document.querySelectorAll(selector).forEach(starBlock => {
        const radios = starBlock.querySelectorAll('input[type="radio"]');
        const labels = starBlock.querySelectorAll('label');
        function updateStars(value) {
            labels.forEach((label, idx) => {
                const star = label.querySelector('i');
                if (star) star.classList.toggle('filled', idx < value);
            });
        }
        let checked = starBlock.querySelector('input:checked');
        updateStars(checked ? parseInt(checked.value) : 0);

        labels.forEach((label, idx) => {
            label.addEventListener('mouseenter', () => updateStars(idx + 1));
            label.addEventListener('mouseleave', () => {
                let checked = starBlock.querySelector('input:checked');
                updateStars(checked ? parseInt(checked.value) : 0);
            });
            label.addEventListener('click', () => updateStars(idx + 1));
        });
        radios.forEach((radio) => {
            radio.addEventListener('change', function() {
                updateStars(parseInt(this.value));
            });
        });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    bindStarsInput();
});