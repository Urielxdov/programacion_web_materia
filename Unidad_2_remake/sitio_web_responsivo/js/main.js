const workItems = document.querySelectorAll('.work .item');
const workItemsCount = workItems.length;
let currentIndex = 0;
const closeButton = document.querySelector('#close-button');
const prevButton = document.querySelector('#prev-button');
const nextButton = document.querySelector('#next-button');
const openCloseButton = document.querySelector('#buttonOpenCloseMenu');
const menuMobileItems = document.querySelector('#menu-mobile-items');

// Menu movil: alterna la clase que oculta los elementos
openCloseButton.addEventListener('click', e => {
    e.preventDefault();
    menuMobileItems.classList.toggle('menu-mobile-closed');
});

// Al dar clic en un trabajo se abre la galeria en ese elemento
workItems.forEach(item => {
    item.addEventListener('click', e => {
        e.preventDefault();
        currentIndex = parseInt(item.getAttribute('data-id'));
        loadGalleryItem(currentIndex);
        document.querySelector('#screen').style['animation-name'] = 'fade-in';
        document.querySelector('body').style['overflow'] = 'hidden';
        setTimeout(() => {
            document.querySelector('#details-container').style.display = 'block';
        }, 1000);
        setTimeout(() => {
            document.querySelector('#screen').style = '';
        }, 2000);
    });
});

closeButton.addEventListener('click', e => {
    e.preventDefault();
    document.querySelector('#screen').style['animation-name'] = 'fade-in';
    document.querySelector('body').style['overflow'] = 'auto';
    setTimeout(() => {
        document.querySelector('#details-container').style.display = 'none';
    }, 1000);
    setTimeout(() => {
        document.querySelector('#screen').style = '';
    }, 2000);
});

prevButton.addEventListener('click', e => {
    e.preventDefault();
    if (currentIndex - 1 < 0) {
        return;
    }
    currentIndex--;
    loadGalleryItem(currentIndex);
});

nextButton.addEventListener('click', e => {
    e.preventDefault();
    if (currentIndex + 1 == workItemsCount) {
        return;
    }
    currentIndex++;
    loadGalleryItem(currentIndex);
});

function loadGalleryItem(index) {
    document.querySelectorAll('#details-container .item').forEach(item => {
        item.classList.add('item-hide');
    });
    document.querySelectorAll('#details-container .item')[index].classList.remove('item-hide');
}
