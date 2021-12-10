@component('mail::message')
# JesusTips Manager | Recuperação de Senha

Clique no botão abaixo para recuperar a sua senha.

@component('mail::button', ['url' => 'http://localhost:8000/api?token='.$token])
Recuperar Senha
@endcomponent

Obrigado,<br>
{{ config('app.name') }}
@endcomponent
