// User Page Logic
const USER_KEY = 'bia_modas_user';

function getUserData() {
    return JSON.parse(localStorage.getItem(USER_KEY)) || {
        nome: '',
        email: '',
        telefone: '',
        endereco: '',
        numero: '',
        bairro: '',
        cidade: '',
        estado: '',
        cep: ''
    };
}

function saveUserData(data) {
    localStorage.setItem(USER_KEY, JSON.stringify(data));
}

function renderUserForm() {
    const data = getUserData();
    const fields = ['nome', 'email', 'telefone', 'endereco', 'numero', 'bairro', 'cidade', 'estado', 'cep'];
    fields.forEach(field => {
        const el = document.getElementById('user-' + field);
        if (el) el.value = data[field] || '';
    });
}

function handleUserFormSubmit(e) {
    e.preventDefault();
    const data = {
        nome: document.getElementById('user-nome').value,
        email: document.getElementById('user-email').value,
        telefone: document.getElementById('user-telefone').value,
        endereco: document.getElementById('user-endereco').value,
        numero: document.getElementById('user-numero').value,
        bairro: document.getElementById('user-bairro').value,
        cidade: document.getElementById('user-cidade').value,
        estado: document.getElementById('user-estado').value,
        cep: document.getElementById('user-cep').value
    };
    saveUserData(data);
    showToast('Dados salvos com sucesso!');
}

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('user-form');
    if (form) {
        renderUserForm();
        form.addEventListener('submit', handleUserFormSubmit);
    }
});
