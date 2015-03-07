<?php
class TransaccionEstaCerradaExeption extends Exception {

    protected $message = 'La transacción no puede modificarse por que esta aprobada o revisada.';
}