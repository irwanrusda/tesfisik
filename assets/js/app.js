const menuToggle = document.getElementById('menuToggle');
const sidebar = document.getElementById('sidebar');
menuToggle?.addEventListener('click', () => sidebar?.classList.toggle('open'));

document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm || 'Lanjutkan?')) event.preventDefault();
    });
});

const heightInput = document.querySelector('[data-height]');
const weightInput = document.querySelector('[data-weight]');
const bmiInput = document.querySelector('[data-bmi]');
function updateBmi() {
    if (!heightInput || !weightInput || !bmiInput) return;
    const height = Number(heightInput.value) / 100;
    const weight = Number(weightInput.value);
    bmiInput.value = height > 0 && weight > 0 ? (weight / (height * height)).toFixed(2) : '';
}
heightInput?.addEventListener('input', updateBmi);
weightInput?.addEventListener('input', updateBmi);

const athleteDropdown = document.querySelector('[data-athlete-dropdown]');
const athleteCombobox = document.querySelector('[data-athlete-combobox]');
const athleteValue = document.querySelector('[data-athlete-value]');
const athleteOptions = Array.from(document.querySelectorAll('[data-athlete-option]'));
const athleteEmpty = document.querySelector('[data-athlete-empty]');

function updateAthleteFields(option) {
    const gender = option?.dataset.gender || '';
    const values = {
        '[data-athlete-name]': option?.dataset.name || '',
        '[data-athlete-sport]': option?.dataset.sport || '',
        '[data-athlete-gender]': gender,
        '[data-athlete-gender-label]': gender === 'L' ? 'Laki-Laki' : gender === 'P' ? 'Perempuan' : '',
        '[data-athlete-status]': option?.dataset.status || '',
    };
    Object.entries(values).forEach(([selector, value]) => {
        const input = document.querySelector(selector);
        if (input) input.value = value;
    });
}

function openAthleteDropdown() {
    athleteDropdown?.classList.add('open');
    athleteCombobox?.setAttribute('aria-expanded', 'true');
}

function closeAthleteDropdown() {
    athleteDropdown?.classList.remove('open');
    athleteCombobox?.setAttribute('aria-expanded', 'false');
}

function filterAthletes() {
    const keyword = athleteCombobox?.value.trim().toLocaleLowerCase('id-ID') || '';
    let visible = 0;
    athleteOptions.forEach((option) => {
        const matches = option.dataset.name.toLocaleLowerCase('id-ID').includes(keyword)
            || option.dataset.sport.toLocaleLowerCase('id-ID').includes(keyword);
        option.hidden = !matches;
        if (matches) visible++;
    });
    if (athleteEmpty) athleteEmpty.hidden = visible > 0;
    openAthleteDropdown();
}

athleteCombobox?.addEventListener('focus', filterAthletes);
athleteCombobox?.addEventListener('input', () => {
    if (athleteValue) athleteValue.value = '';
    updateAthleteFields(null);
    filterAthletes();
});

athleteOptions.forEach((option) => {
    option.addEventListener('click', () => {
        athleteOptions.forEach((item) => item.setAttribute('aria-selected', 'false'));
        option.setAttribute('aria-selected', 'true');
        if (athleteValue) athleteValue.value = option.dataset.id;
        if (athleteCombobox) athleteCombobox.value = `${option.dataset.name} - ${option.dataset.sport}`;
        updateAthleteFields(option);
        closeAthleteDropdown();
    });
});

document.addEventListener('click', (event) => {
    if (athleteDropdown && !athleteDropdown.contains(event.target)) closeAthleteDropdown();
});

athleteDropdown?.closest('form')?.addEventListener('submit', (event) => {
    if (!athleteValue?.value) {
        event.preventDefault();
        athleteCombobox?.setCustomValidity('Pilih atlet dari hasil pencarian.');
        athleteCombobox?.reportValidity();
        openAthleteDropdown();
    } else {
        athleteCombobox?.setCustomValidity('');
    }
});

const selectedAthlete = athleteOptions.find((option) => option.dataset.id === athleteValue?.value);
if (selectedAthlete) {
    athleteCombobox.value = `${selectedAthlete.dataset.name} - ${selectedAthlete.dataset.sport}`;
    updateAthleteFields(selectedAthlete);
}

const photoInputs = Array.from(document.querySelectorAll('[data-photo-input]'));
const photoPreview = document.querySelector('[data-photo-preview]');

function compressPhoto(file) {
    return new Promise((resolve, reject) => {
        if (!file.type.startsWith('image/')) {
            reject(new Error(`${file.name} bukan file gambar.`));
            return;
        }

        const image = new Image();
        const objectUrl = URL.createObjectURL(file);
        image.onload = () => {
            URL.revokeObjectURL(objectUrl);
            const maxDimension = 1600;
            const scale = Math.min(1, maxDimension / Math.max(image.naturalWidth, image.naturalHeight));
            const width = Math.max(1, Math.round(image.naturalWidth * scale));
            const height = Math.max(1, Math.round(image.naturalHeight * scale));
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            const context = canvas.getContext('2d');
            context.fillStyle = '#fff';
            context.fillRect(0, 0, width, height);
            context.drawImage(image, 0, 0, width, height);
            canvas.toBlob((blob) => {
                if (!blob) {
                    reject(new Error(`${file.name} gagal dikompresi.`));
                    return;
                }
                const baseName = file.name.replace(/\.[^.]+$/, '') || 'dokumentasi';
                resolve(new File([blob], `${baseName}.jpg`, { type: 'image/jpeg', lastModified: Date.now() }));
            }, 'image/jpeg', 0.78);
        };
        image.onerror = () => {
            URL.revokeObjectURL(objectUrl);
            reject(new Error(`${file.name} tidak dapat dibaca oleh browser.`));
        };
        image.src = objectUrl;
    });
}

function renderPhotoPreviews() {
    if (!photoPreview) return;
    photoPreview.replaceChildren();
    photoInputs.flatMap((input) => Array.from(input.files || [])).forEach((file) => {
        const card = document.createElement('div');
        card.className = 'photo-preview-card';
        const image = document.createElement('img');
        image.alt = file.name;
        image.src = URL.createObjectURL(file);
        image.addEventListener('load', () => URL.revokeObjectURL(image.src), { once: true });
        const caption = document.createElement('span');
        caption.textContent = `${file.name} · ${Math.ceil(file.size / 1024)} KB`;
        card.append(image, caption);
        photoPreview.append(card);
    });
}

photoInputs.forEach((input) => {
    input.addEventListener('change', async () => {
        const otherCount = photoInputs
            .filter((item) => item !== input)
            .reduce((total, item) => total + (item.files?.length || 0), 0);
        const selectedFiles = Array.from(input.files || []);
        if (otherCount + selectedFiles.length > 10) {
            input.value = '';
            window.alert('Maksimal 10 foto dapat diunggah dalam satu penyimpanan.');
            renderPhotoPreviews();
            return;
        }

        input.disabled = true;
        try {
            const compressedFiles = [];
            for (const file of selectedFiles) {
                compressedFiles.push(await compressPhoto(file));
            }
            const transfer = new DataTransfer();
            compressedFiles.forEach((file) => transfer.items.add(file));
            input.files = transfer.files;
            renderPhotoPreviews();
        } catch (error) {
            input.value = '';
            renderPhotoPreviews();
            window.alert(error.message || 'Foto gagal diproses.');
        } finally {
            input.disabled = false;
        }
    });
});
