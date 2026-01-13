<?php
/**
 * Serviço de envio de emails
 * 
 * @package App\Services
 * @author Sistema Administrativo MVC
 */

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private PHPMailer $mailer;
    
    /**
     * Construtor do serviço de email
     */
    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->configureMail();
    }
    
    /**
     * Configura o PHPMailer
     * 
     * @return void
     */
    private function configureMail(): void
    {
        try {
            $this->mailer->isSMTP();
            $this->mailer->Host = $_ENV['MAIL_HOST'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $_ENV['MAIL_USERNAME'];
            $this->mailer->Password = $_ENV['MAIL_PASSWORD'];
            $this->mailer->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
            $this->mailer->Port = $_ENV['MAIL_PORT'];
            $this->mailer->CharSet = 'UTF-8';
            
            // Configurações padrão
            $this->mailer->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
            
        } catch (Exception $e) {
            error_log("Erro na configuração do email: " . $e->getMessage());
        }
    }
    
    /**
     * Envia email de reset de senha
     * 
     * @param string $email
     * @param string $nome
     * @param string $resetUrl
     * @return bool
     */
    public function sendPasswordReset(string $email, string $nome, string $resetUrl): bool
    {
        try {
            $this->mailer->addAddress($email, $nome);
            $this->mailer->Subject = 'Redefinição de Senha - ' . $_ENV['APP_NAME'];
            
            $body = $this->getPasswordResetTemplate($nome, $resetUrl);
            $this->mailer->msgHTML($body);
            
            return $this->mailer->send();
            
        } catch (Exception $e) {
            error_log("Erro ao enviar email: " . $e->getMessage());
            return false;
        } finally {
            $this->mailer->clearAddresses();
        }
    }
    
    /**
     * Envia email de boas-vindas
     * 
     * @param string $email
     * @param string $nome
     * @param string $loginUrl
     * @return bool
     */
    public function sendWelcome(string $email, string $nome, string $loginUrl): bool
    {
        try {
            $this->mailer->addAddress($email, $nome);
            $this->mailer->Subject = 'Bem-vindo ao ' . $_ENV['APP_NAME'];
            
            $body = $this->getWelcomeTemplate($nome, $loginUrl);
            $this->mailer->msgHTML($body);
            
            return $this->mailer->send();
            
        } catch (Exception $e) {
            error_log("Erro ao enviar email: " . $e->getMessage());
            return false;
        } finally {
            $this->mailer->clearAddresses();
        }
    }
    
    /**
     * Template de email para reset de senha
     * 
     * @param string $nome
     * @param string $resetUrl
     * @return string
     */
    private function getPasswordResetTemplate(string $nome, string $resetUrl): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Redefinição de Senha</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #007bff; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f8f9fa; }
                .button { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>" . $_ENV['APP_NAME'] . "</h1>
                </div>
                <div class='content'>
                    <h2>Olá, {$nome}!</h2>
                    <p>Você solicitou a redefinição de sua senha. Clique no botão abaixo para criar uma nova senha:</p>
                    <p><a href='{$resetUrl}' class='button'>Redefinir Senha</a></p>
                    <p>Se você não solicitou esta redefinição, ignore este email.</p>
                    <p><strong>Este link expira em 1 hora.</strong></p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " " . $_ENV['APP_NAME'] . ". Todos os direitos reservados.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Template de email de boas-vindas
     * 
     * @param string $nome
     * @param string $loginUrl
     * @return string
     */
    private function getWelcomeTemplate(string $nome, string $loginUrl): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Bem-vindo</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #28a745; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f8f9fa; }
                .button { display: inline-block; padding: 12px 24px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Bem-vindo ao " . $_ENV['APP_NAME'] . "</h1>
                </div>
                <div class='content'>
                    <h2>Olá, {$nome}!</h2>
                    <p>Sua conta foi criada com sucesso. Agora você pode acessar o sistema:</p>
                    <p><a href='{$loginUrl}' class='button'>Fazer Login</a></p>
                    <p>Se você tiver alguma dúvida, entre em contato conosco.</p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " " . $_ENV['APP_NAME'] . ". Todos os direitos reservados.</p>
                </div>
            </div>
        </body>
        </html>";
    }
}