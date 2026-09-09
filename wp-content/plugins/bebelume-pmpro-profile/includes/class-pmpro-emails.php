<?php
/**
 * BBL_PMPro_Emails — E-mails transacionais do PMPro em pt-BR
 *
 * Reescreve os principais templates do PMPro em português, com a identidade
 * visual da Bebelume (mesmo shell HTML do BBL_Emails). Os placeholders
 * !!variavel!! continuam válidos: o PMPro os resolve depois do filtro.
 *
 * Hooks em prioridade 20 para rodar DEPOIS do pmpro_kses (prio 11), de forma
 * que o HTML da marca (estilos inline) não seja sanitizado pelo WordPress.
 *
 * @package Bebelume_PMPro_Profile
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BBL_PMPro_Emails {

    private static $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        if ( ! class_exists( 'PMProEmail' ) ) {
            return;
        }
        $this->hooks();
    }

    private function hooks(): void {
        add_filter( 'pmpro_email_subject', [ $this, 'subject' ], 20, 2 );
        add_filter( 'pmpro_email_body',    [ $this, 'body' ],    20, 2 );
        add_filter( 'pmpro_email_data',    [ $this, 'data' ],    20, 2 );
        // O PMPro embrulha o body com o header/footer padrão ("Dear ...",
        // "Respectfully, ...") DEPOIS do filtro de body. Quando o BBL já montou
        // o HTML completo (shell da marca), remove esses fragmentos em inglês.
        add_filter( 'pmpro_email_header', [ $this, 'strip_fragments_if_branded' ], 20, 2 );
        add_filter( 'pmpro_email_footer', [ $this, 'strip_fragments_if_branded' ], 20, 2 );
    }

    public function strip_fragments_if_branded( $fragment, $email ) {
        if ( strpos( ltrim( (string) $email->body ), '<!DOCTYPE' ) === 0 ) {
            return '';
        }
        return $fragment;
    }

    /**
     * Localiza strings em inglês que o PMPro injeta dentro dos dados do e-mail
     * (e que o filtro de body não alcança). Hoje só o !!membership_change!! dos
     * templates admin_change/admin_change_admin cai nesse caso.
     */
    public function data( $data, $email ) {
        $template = ! empty( $email->template ) ? $email->template : '';
        if ( in_array( $template, [ 'admin_change', 'admin_change_admin' ], true ) && ! empty( $data['membership_change'] ) ) {
            $map = [
                'Your membership has been cancelled.'                                            => 'Sua assinatura foi cancelada.',
                'You can view your current memberships by logging in and visiting your membership account page.' => 'Você pode ver seus planos atuais entrando na sua conta.',
                "The user's membership has been cancelled."                                       => 'A assinatura do usuário foi cancelada.',
                "You can view the user's current memberships from their Edit Member page."        => 'Você pode ver os planos atuais do usuário pela página de edição do membro.',
            ];
            if ( isset( $map[ $data['membership_change'] ] ) ) {
                $data['membership_change'] = $map[ $data['membership_change'] ];
            }
        }
        return $data;
    }

    // =========================================================================
    // FILTROS
    // =========================================================================

    public function subject( $subject, $email ) {
        $map = $this->subjects();
        $template = ! empty( $email->template ) ? $email->template : '';
        if ( isset( $map[ $template ] ) ) {
            return $map[ $template ];
        }
        return $subject;
    }

    public function body( $body, $email ) {
        $map = [
            // Para o assinante.
            'checkout_paid'               => 'body_checkout_paid',
            'checkout_free'               => 'body_checkout_free',
            'checkout_check'              => 'body_checkout_check',
            'cancel'                      => 'body_cancel',
            'cancel_on_next_payment_date' => 'body_cancel_on_next_payment_date',
            'billing'                     => 'body_billing',
            'billing_failure'             => 'body_billing_failure',
            'membership_recurring'        => 'body_membership_recurring',
            'membership_expiring'         => 'body_membership_expiring',
            'membership_expired'          => 'body_membership_expired',
            'invoice'                     => 'body_invoice',
            'credit_card_expiring'        => 'body_credit_card_expiring',
            'refund'                      => 'body_refund',
            'payment_action'              => 'body_payment_action',
            'admin_change'                => 'body_admin_change',
            // Notificações ao administrador.
            'checkout_paid_admin'               => 'body_checkout_paid_admin',
            'checkout_free_admin'               => 'body_checkout_free_admin',
            'checkout_check_admin'              => 'body_checkout_check_admin',
            'cancel_admin'                      => 'body_cancel_admin',
            'cancel_on_next_payment_date_admin' => 'body_cancel_on_next_payment_date_admin',
            'billing_admin'                     => 'body_billing_admin',
            'billing_failure_admin'             => 'body_billing_failure_admin',
            'refund_admin'                      => 'body_refund_admin',
            'payment_action_admin'              => 'body_payment_action_admin',
            'admin_change_admin'                => 'body_admin_change_admin',
        ];
        $template = ! empty( $email->template ) ? $email->template : '';
        if ( isset( $map[ $template ] ) && method_exists( $this, $map[ $template ] ) ) {
            return call_user_func( [ $this, $map[ $template ] ], $email );
        }
        return $body;
    }

    private function subjects(): array {
        return [
            'checkout_paid'               => 'Bem-vindo(a) ao Bebelume! Sua assinatura está ativa',
            'checkout_free'               => 'Bem-vindo(a) ao Bebelume! Sua assinatura está ativa',
            'checkout_check'              => 'Bem-vindo(a) ao Bebelume! Sua assinatura está ativa',
            'cancel'                      => 'Sua assinatura no Bebelume foi cancelada',
            'cancel_on_next_payment_date' => 'Seu acesso ao Bebelume continua até !!enddate!!',
            'billing'                     => 'Seus dados de cobrança foram atualizados',
            'billing_failure'             => 'Ops! Não conseguimos cobrar sua assinatura',
            'membership_recurring'        => 'Lembrete: sua assinatura será renovada em !!renewaldate!!',
            'membership_expiring'         => 'Sua assinatura expira em !!enddate!!',
            'membership_expired'          => 'Sua assinatura expirou — volte para o Bebelume',
            'invoice'                     => 'Recibo da sua assinatura Bebelume',
            'credit_card_expiring'        => 'Seu cartão cadastrado está vencendo',
            'refund'                      => 'Seu reembolso foi processado',
            'payment_action'              => 'Confirme seu pagamento para ativar sua assinatura',
            'admin_change'                => 'Sua assinatura no Bebelume foi atualizada',
            // Notificações ao administrador.
            'checkout_paid_admin'               => 'Novo checkout: !!membership_level_name!!',
            'checkout_free_admin'               => 'Nova assinatura gratuita: !!membership_level_name!!',
            'checkout_check_admin'              => 'Novo pedido: !!membership_level_name!!',
            'cancel_admin'                      => 'Assinatura cancelada: !!display_name!! · !!membership_level_name!!',
            'cancel_on_next_payment_date_admin' => 'Assinatura será cancelada: !!display_name!! · !!membership_level_name!!',
            'billing_admin'                     => 'Dados de cobrança atualizados: !!display_name!!',
            'billing_failure_admin'             => 'Falha de cobrança: !!display_name!! · !!membership_level_name!!',
            'refund_admin'                      => 'Pedido #!!order_id!! reembolsado',
            'payment_action_admin'              => 'Pagamento precisa de confirmação: !!display_name!!',
            'admin_change_admin'                => 'Assinatura alterada no painel: !!display_name!!',
        ];
    }

    // =========================================================================
    // TEMPLATES
    // =========================================================================

    private function body_checkout_paid( $email ): string {
        $d = $email->data;

        $content =
            $this->para( 'Olá, ' . esc_html( $d['display_name'] ?? '' ) . '!' ) .
            $this->para( 'Sua assinatura do plano <strong>' . esc_html( $d['membership_level_name'] ?? '' ) . '</strong> está ativa. Já pode acessar todas as aulas, músicas e atividades do portal.' ) .
            ( ! empty( $d['membership_level_confirmation_message'] )
                ? '<div style="margin:0 0 18px;line-height:1.6;">!!membership_level_confirmation_message!!</div>'
                : '' ) .
            $this->details( [
                'Plano'  => esc_html( $d['membership_level_name'] ?? '' ),
                'Valor'  => '!!membership_cost!!',
                'Pedido' => '#!!order_id!! · !!order_date!!',
                'Total'  => '!!order_total!!',
            ] ) .
            $this->card_block( '!!cardtype!!', '!!accountnumber!!', '!!expirationmonth!!', '!!expirationyear!!' ) .
            $this->button( '!!login_url!!', 'Acessar minha conta' ) .
            $this->muted( '<a href="!!order_url!!" style="color:#b0bcc8;text-decoration:underline;">Ver recibo completo</a>' );

        return $this->shell( 'Sua assinatura está ativa', $content );
    }

    private function body_cancel( $email ): string {
        $d = $email->data;

        $content =
            $this->para( 'Olá, ' . esc_html( $d['display_name'] ?? '' ) . '!' ) .
            $this->para( 'Conforme solicitado, sua assinatura do plano <strong>' . esc_html( $d['membership_level_name'] ?? '' ) . '</strong> foi cancelada e você não será mais cobrado.' ) .
            $this->para( 'Sentiremos sua falta. Se quiser voltar, é só reassinar — todo o conteúdo continua te esperando.' ) .
            $this->button( '!!renew_url!!', 'Ver planos e reativar' ) .
            $this->muted( 'Não foi você? Fale com a gente: !!siteemail!!' );

        return $this->shell( 'Assinatura cancelada', $content );
    }

    private function body_cancel_on_next_payment_date( $email ): string {
        $d = $email->data;

        $content =
            $this->para( 'Olá, ' . esc_html( $d['display_name'] ?? '' ) . '!' ) .
            $this->para( 'Sua assinatura do plano <strong>' . esc_html( $d['membership_level_name'] ?? '' ) . '</strong> foi cancelada e você não será mais cobrado.' ) .
            $this->para( 'Seu acesso ao conteúdo continua liberado até <strong>!!enddate!!</strong> — aproveite até o último dia.' ) .
            $this->para( 'Mudou de ideia? Você pode voltar quando quiser.' ) .
            $this->button( '!!levels_url!!', 'Ver planos' ) .
            $this->muted( 'Não foi você? Fale com a gente: !!siteemail!!' );

        return $this->shell( 'Você não será mais cobrado', $content );
    }

    private function body_billing_failure( $email ): string {
        $d = $email->data;

        $content =
            $this->para( 'Olá, ' . esc_html( $d['display_name'] ?? '' ) . '!' ) .
            $this->para( 'Não conseguimos realizar a cobrança da sua assinatura do plano <strong>' . esc_html( $d['membership_level_name'] ?? '' ) . '</strong>.' ) .
            $this->para( 'Atualize os dados do seu cartão para não perder o acesso ao conteúdo.' ) .
            $this->billing_failure_reason_html( $email ) .
            $this->card_block( '!!cardtype!!', '!!accountnumber!!', '!!expirationmonth!!', '!!expirationyear!!' ) .
            $this->button( '!!login_url!!', 'Atualizar meu cartão' ) .
            $this->muted( 'Qualquer dúvida, fale com a gente: !!siteemail!!' );

        return $this->shell( 'Precisamos atualizar seu pagamento', $content );
    }

    /**
     * Bloco opcional com o motivo da recusa (T02). Inativo por padrão: só renderiza
     * quando a option bbl_billing_failure_decline_email estiver ligada (=1), o que
     * exige aprovação prévia do texto (regra: nenhum e-mail real sem aprovação).
     */
    private function billing_failure_reason_html( $email ): string {
        if ( ! get_option( 'bbl_billing_failure_decline_email', 0 ) ) {
            return '';
        }
        if ( ! class_exists( 'BBL_Billing_Failure' ) ) {
            return '';
        }
        $d      = $email->data;
        $reason = BBL_Billing_Failure::get_instance()->consume_decline_for_email( $d['user_email'] ?? '' );
        if ( empty( $reason ) ) {
            return '';
        }
        $label = BBL_Billing_Failure::friendly_decline( $reason['code'] ?? '', $reason['message'] ?? '' );

        return '<p style="margin:0 0 18px;padding:12px 16px;background:#fdf2f4;border-radius:10px;line-height:1.6;color:#7a1f32;">'
            . 'O pagamento foi recusado porque <strong>' . esc_html( $label ) . '</strong>. '
            . 'Atualize os dados do cartão para tentarmos novamente.'
            . '</p>';
    }

    private function body_membership_recurring( $email ): string {
        $d = $email->data;

        $content =
            $this->para( 'Olá, ' . esc_html( $d['display_name'] ?? '' ) . '!' ) .
            $this->para( 'Este é só um lembrete: sua assinatura do plano <strong>' . esc_html( $d['membership_level_name'] ?? '' ) . '</strong> será renovada automaticamente em <strong>!!renewaldate!!</strong>.' ) .
            $this->para( 'Nenhuma ação é necessária — o valor de <strong>!!membership_cost!!</strong> será cobrado no cartão cadastrado.' ) .
            $this->muted( '<a href="!!cancel_url!!" style="color:#b0bcc8;text-decoration:underline;">Não quer renovar? Gerencie sua assinatura</a>' );

        return $this->shell( 'Sua assinatura será renovada', $content );
    }

    private function body_membership_expiring( $email ): string {
        $d = $email->data;

        $content =
            $this->para( 'Olá, ' . esc_html( $d['display_name'] ?? '' ) . '!' ) .
            $this->para( 'Sua assinatura do plano <strong>' . esc_html( $d['membership_level_name'] ?? '' ) . '</strong> expira em <strong>!!enddate!!</strong>.' ) .
            $this->para( 'Renove para continuar acessando todo o conteúdo do Bebelume sem interrupção.' ) .
            $this->button( '!!renew_url!!', 'Renovar agora' ) .
            $this->muted( '<a href="!!login_url!!" style="color:#b0bcc8;text-decoration:underline;">Gerenciar minha conta</a>' );

        return $this->shell( 'Seu acesso expira em breve', $content );
    }

    private function body_membership_expired( $email ): string {
        $d = $email->data;

        $content =
            $this->para( 'Olá, ' . esc_html( $d['display_name'] ?? '' ) . '!' ) .
            $this->para( 'Sua assinatura do plano <strong>' . esc_html( $d['membership_level_name'] ?? '' ) . '</strong> expirou. Que tal voltar?' ) .
            $this->para( 'As aulas, músicas e atividades do Bebelume continuam te esperando.' ) .
            $this->button( '!!levels_url!!', 'Ver planos' ) .
            $this->muted( '<a href="!!login_url!!" style="color:#b0bcc8;text-decoration:underline;">Gerenciar minha conta</a>' );

        return $this->shell( 'Sentimos sua falta', $content );
    }

    private function body_invoice( $email ): string {
        $d = $email->data;

        $content =
            $this->para( 'Olá, ' . esc_html( $d['display_name'] ?? '' ) . '!' ) .
            $this->para( 'Aqui está o recibo da sua assinatura do plano <strong>' . esc_html( $d['membership_level_name'] ?? '' ) . '</strong>.' ) .
            $this->details( [
                'Pedido' => '#!!order_id!! · !!order_date!!',
                'Total'  => '!!order_total!!',
            ] ) .
            $this->card_block( '!!cardtype!!', '!!accountnumber!!', '!!expirationmonth!!', '!!expirationyear!!' ) .
            $this->button( '!!order_url!!', 'Ver recibo completo' ) .
            $this->muted( 'Obrigado por fazer parte do Bebelume!' );

        return $this->shell( 'Recibo da sua assinatura', $content );
    }

    private function body_credit_card_expiring( $email ): string {
        $d = $email->data;

        $content =
            $this->para( 'Olá, ' . esc_html( $d['display_name'] ?? '' ) . '!' ) .
            $this->para( 'O cartão cadastrado para a sua assinatura do plano <strong>' . esc_html( $d['membership_level_name'] ?? '' ) . '</strong> vence em breve.' ) .
            $this->card_block( '!!cardtype!!', '!!accountnumber!!', '!!expirationmonth!!', '!!expirationyear!!' ) .
            $this->para( 'Atualize os dados do cartão para não interromper seu acesso.' ) .
            $this->button( '!!login_url!!', 'Atualizar cartão' ) .
            $this->muted( 'Qualquer dúvida, fale com a gente: !!siteemail!!' );

        return $this->shell( 'Atualize seu cartão', $content );
    }

    private function body_refund( $email ): string {
        $d = $email->data;

        $content =
            $this->para( 'Olá, ' . esc_html( $d['display_name'] ?? '' ) . '!' ) .
            $this->para( 'O pedido <strong>#!!order_id!!</strong> foi reembolsado em <strong>!!refund_date!!</strong>.' ) .
            $this->details( [
                'Pedido'  => '#!!order_id!!',
                'Reembolso' => '!!order_total!!',
            ] ) .
            $this->para( 'O valor será devolvido à forma de pagamento original.' ) .
            $this->button( '!!order_url!!', 'Ver detalhes' ) .
            $this->muted( 'Qualquer dúvida, fale com a gente: !!siteemail!!' );

        return $this->shell( 'Reembolso realizado', $content );
    }

    // =========================================================================
    // TEMPLATES NOVOS — restante dos templates do PMPro (assinante + admin)
    // =========================================================================

    private function body_checkout_free( $email ): string {
        $d = $email->data;

        $content =
            $this->para( 'Olá, ' . esc_html( $d['display_name'] ?? '' ) . '!' ) .
            $this->para( 'Sua assinatura do plano <strong>' . esc_html( $d['membership_level_name'] ?? '' ) . '</strong> está ativa. Já pode acessar todas as aulas, músicas e atividades do portal.' ) .
            ( ! empty( $d['membership_level_confirmation_message'] )
                ? '<div style="margin:0 0 18px;line-height:1.6;">!!membership_level_confirmation_message!!</div>'
                : '' ) .
            $this->details( [
                'Plano'  => esc_html( $d['membership_level_name'] ?? '' ),
                'Pedido' => '#!!order_id!! · !!order_date!!',
            ] ) .
            $this->cta_button( $d, 'order_url', 'Ver detalhes da assinatura' ) .
            $this->muted( 'Obrigado por fazer parte do Bebelume!' );

        return $this->shell( 'Sua assinatura está ativa', $content );
    }

    private function body_checkout_check( $email ): string {
        $d = $email->data;

        $content =
            $this->para( 'Olá, ' . esc_html( $d['display_name'] ?? '' ) . '!' ) .
            $this->para( 'Recebemos seu pedido do plano <strong>' . esc_html( $d['membership_level_name'] ?? '' ) . '</strong>. Sua assinatura será ativada assim que confirmarmos o pagamento.' ) .
            $this->details( [
                'Plano'  => esc_html( $d['membership_level_name'] ?? '' ),
                'Pedido' => '#!!order_id!! · !!order_date!!',
                'Total'  => '!!order_total!!',
            ] ) .
            ( ! empty( $d['instructions'] ) ? '<div style="margin:0 0 18px;line-height:1.6;">!!instructions!!</div>' : '' ) .
            $this->cta_button( $d, 'order_url', 'Ver detalhes do pedido' ) .
            $this->muted( 'Qualquer dúvida, fale com a gente: !!siteemail!!' );

        return $this->shell( 'Pedido recebido', $content );
    }

    private function body_billing( $email ): string {
        $d = $email->data;

        $content =
            $this->para( 'Olá, ' . esc_html( $d['display_name'] ?? '' ) . '!' ) .
            $this->para( 'Seus dados de cobrança da assinatura do plano <strong>' . esc_html( $d['membership_level_name'] ?? '' ) . '</strong> foram atualizados com sucesso.' ) .
            $this->card_block( '!!cardtype!!', '!!accountnumber!!', '!!expirationmonth!!', '!!expirationyear!!' ) .
            ( ! empty( $d['billing_address'] ) ? $this->address_block( $d['billing_address'] ) : '' ) .
            $this->cta_button( $d, 'login_url', 'Gerenciar minha conta' ) .
            $this->muted( 'Qualquer dúvida, fale com a gente: !!siteemail!!' );

        return $this->shell( 'Dados de cobrança atualizados', $content );
    }

    private function body_payment_action( $email ): string {
        $d = $email->data;

        $content =
            $this->para( 'Olá, ' . esc_html( $d['display_name'] ?? '' ) . '!' ) .
            $this->para( 'Para ativar sua assinatura no Bebelume, precisamos que você <strong>confirme o pagamento</strong> com o seu banco ou operadora do cartão.' ) .
            $this->para( 'É só seguir os passos de verificação no link abaixo — leva menos de um minuto.' ) .
            $this->cta_button( $d, 'order_url', 'Confirmar pagamento' ) .
            $this->cta_muted( $d, 'levels_url', 'Ver outros planos' ) .
            $this->muted( 'Qualquer dúvida, fale com a gente: !!siteemail!!' );

        return $this->shell( 'Confirme seu pagamento', $content );
    }

    private function body_admin_change( $email ): string {
        $d = $email->data;

        $content =
            $this->para( 'Olá, ' . esc_html( $d['display_name'] ?? '' ) . '!' ) .
            $this->para( 'Sua assinatura no Bebelume foi atualizada pela nossa equipe de suporte.' ) .
            $this->para( '!!membership_change!!' ) .
            $this->muted( 'Qualquer dúvida, fale com a gente: !!siteemail!!' );

        return $this->shell( 'Sua assinatura foi atualizada', $content );
    }

    private function body_checkout_paid_admin( $email ): string {
        $d = $email->data;

        $content =
            $this->para( 'Entrou um novo membro no Bebelume por checkout pago.' ) .
            $this->member_admin_details( $d, 'Novo checkout no Bebelume', true ) .
            ( ! empty( $d['discount_code'] )
                ? $this->para( 'Cupom usado: <strong>!!discount_code!!</strong>' )
                : '' ) .
            $this->cta_button( $d, 'order_url', 'Ver detalhes do pedido' ) .
            $this->cta_muted( $d, 'login_url', 'Abrir administração do WordPress' );

        return $this->shell( 'Novo checkout pago', $content );
    }

    private function body_checkout_free_admin( $email ): string {
        $d = $email->data;

        $content =
            $this->para( 'Foi criada uma nova assinatura gratuita no Bebelume.' ) .
            $this->member_admin_details( $d, 'Nova assinatura gratuita', false ) .
            $this->cta_button( $d, 'order_url', 'Ver detalhes' ) .
            $this->cta_muted( $d, 'login_url', 'Abrir administração do WordPress' );

        return $this->shell( 'Nova assinatura gratuita', $content );
    }

    private function body_checkout_check_admin( $email ): string {
        $d = $email->data;

        $content =
            $this->para( 'Foi criado um novo pedido no Bebelume com pagamento via <strong>!!check_gateway_label!!</strong>.' ) .
            $this->member_admin_details( $d, 'Novo pedido (pagamento pendente)', true ) .
            $this->cta_button( $d, 'order_url', 'Ver detalhes do pedido' ) .
            $this->cta_muted( $d, 'login_url', 'Abrir administração do WordPress' );

        return $this->shell( 'Novo pedido no Bebelume', $content );
    }

    private function body_cancel_admin( $email ): string {
        $d = $email->data;

        $content =
            $this->para( 'A assinatura do membro abaixo foi <strong>cancelada</strong>.' ) .
            $this->member_admin_details( $d, 'Assinatura cancelada', false ) .
            $this->cta_muted( $d, 'login_url', 'Abrir administração do WordPress' );

        return $this->shell( 'Assinatura cancelada', $content );
    }

    private function body_cancel_on_next_payment_date_admin( $email ): string {
        $d = $email->data;

        $content =
            $this->para( 'O membro abaixo pediu o cancelamento: a assinatura segue ativa até o fim do período já pago e então <strong>não será renovada</strong>.' ) .
            $this->member_admin_details( $d, 'Assinatura será cancelada', false ) .
            $this->cta_muted( $d, 'login_url', 'Abrir administração do WordPress' );

        return $this->shell( 'Assinatura será cancelada', $content );
    }

    private function body_billing_admin( $email ): string {
        $d = $email->data;

        $content =
            $this->para( 'Os dados de cobrança do membro abaixo foram <strong>atualizados</strong>.' ) .
            $this->member_admin_details( $d, 'Dados de cobrança atualizados', false ) .
            $this->card_block( '!!cardtype!!', '!!accountnumber!!', '!!expirationmonth!!', '!!expirationyear!!' ) .
            ( ! empty( $d['billing_address'] ) ? $this->address_block( $d['billing_address'] ) : '' ) .
            $this->cta_muted( $d, 'login_url', 'Abrir administração do WordPress' );

        return $this->shell( 'Dados de cobrança atualizados', $content );
    }

    private function body_billing_failure_admin( $email ): string {
        $d = $email->data;

        $content =
            $this->para( 'A cobrança da assinatura abaixo <strong>falhou</strong>. O membro já foi avisado para atualizar o cartão.' ) .
            $this->member_admin_details( $d, 'Falha de cobrança', false ) .
            $this->card_block( '!!cardtype!!', '!!accountnumber!!', '!!expirationmonth!!', '!!expirationyear!!' ) .
            $this->cta_muted( $d, 'login_url', 'Abrir administração do WordPress' );

        return $this->shell( 'Falha de cobrança', $content );
    }

    private function body_refund_admin( $email ): string {
        $d = $email->data;

        $content =
            $this->para( 'O pedido abaixo foi <strong>reembolsado</strong>.' ) .
            $this->member_admin_details( $d, 'Reembolso realizado', false ) .
            $this->para( 'Valor devolvido: <strong>!!order_total!!</strong> em <strong>!!refund_date!!</strong>' ) .
            $this->cta_muted( $d, 'login_url', 'Abrir administração do WordPress' );

        return $this->shell( 'Pedido reembolsado', $content );
    }

    private function body_payment_action_admin( $email ): string {
        $d = $email->data;

        $content =
            $this->para( 'O pagamento do membro abaixo precisa de <strong>confirmação do cliente</strong> (ex.: verificação bancária / 3D Secure).' ) .
            $this->member_admin_details( $d, 'Pagamento aguardando confirmação', false ) .
            $this->para( 'Já enviamos ao cliente um e-mail com o link para concluir a verificação.' ) .
            $this->cta_button( $d, 'order_url', 'Abrir link de pagamento do membro' );

        return $this->shell( 'Pagamento aguardando confirmação', $content );
    }

    private function body_admin_change_admin( $email ): string {
        $d = $email->data;

        $content =
            $this->para( 'O plano do membro abaixo foi <strong>alterado no painel administrativo</strong>.' ) .
            $this->member_admin_details( $d, 'Assinatura alterada no painel', false ) .
            $this->para( '!!membership_change!!' ) .
            $this->cta_muted( $d, 'login_url', 'Abrir administração do WordPress' );

        return $this->shell( 'Assinatura alterada no painel', $content );
    }

    // =========================================================================
    // HELPERS DE MONTAGEM
    // =========================================================================

    private function shell( string $title, string $content ): string {
        return BBL_Emails::get_instance()->build_email( [
            'title' => $title,
            'body'  => $content,
            'align' => 'center',
        ] );
    }

    private function para( string $html ): string {
        return '<p style="margin:0 0 18px;line-height:1.6;">' . $html . '</p>';
    }

    private function button( string $url, string $label ): string {
        return '<div style="text-align:center;margin:6px 0 10px;">'
            . '<a href="' . $url . '" style="display:inline-block;padding:14px 32px;background:#E73665;color:#ffffff;text-decoration:none;border-radius:10px;font-weight:700;font-size:15px;">'
            . $label . '</a></div>';
    }

    private function muted( string $html ): string {
        return '<p style="margin:14px 0 0;font-size:13px;color:#b0bcc8;text-align:center;">' . $html . '</p>';
    }

    private function details( array $rows ): string {
        $html = '<div style="margin:8px 0 22px;padding:16px 20px;background:#f7f3f9;border-radius:12px;text-align:left;">';
        foreach ( $rows as $label => $value ) {
            if ( '' === $value ) {
                continue;
            }
            $html .= '<p style="margin:0 0 6px;font-size:14px;line-height:1.5;"><strong>' . $label . ':</strong> ' . $value . '</p>';
        }
        $html .= '</div>';
        return $html;
    }

    private function card_block( $cardtype, $accountnumber, $expirationmonth, $expirationyear ): string {
        if ( empty( $cardtype ) && empty( $accountnumber ) ) {
            return '';
        }
        $card = trim( $cardtype . ' final ' . $accountnumber );
        if ( ! empty( $expirationmonth ) ) {
            $card .= ' · expira ' . $expirationmonth . '/' . $expirationyear;
        }
        return '<p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#8a9aa9;text-align:center;">' . $card . '</p>';
    }

    /**
     * Bloco de CTA (botão) que só renderiza se a variável do e-mail existir.
     * Evita vazar "!!url!!" literal em templates que não mandam aquele link.
     */
    private function cta_button( array $d, string $key, string $label ): string {
        if ( empty( $d[ $key ] ?? '' ) ) {
            return '';
        }
        return $this->button( '!!' . $key . '!!', $label );
    }

    /**
     * Link discreto de rodapé, condicionado à variável existir no e-mail.
     */
    private function cta_muted( array $d, string $key, string $label ): string {
        if ( empty( $d[ $key ] ?? '' ) ) {
            return '';
        }
        return $this->muted(
            '<a href="!!' . $key . '!!" style="color:#b0bcc8;text-decoration:underline;">' . $label . '</a>'
        );
    }

    private function address_block( string $address ): string {
        return '<p style="margin:0 0 4px;font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#8a9aa9;"><strong>Endereço de cobrança</strong></p>'
            . '<p style="margin:0 0 18px;line-height:1.6;">' . $address . '</p>';
    }

    /**
     * Bloco compartilhado das notificações ao administrador. Recebe um título de
     * bloco interno ($label) usado como legenda quando o cartão/endereço vem junto.
     */
    private function member_admin_details( array $d, string $label, bool $with_total ): string {
        $rows = [
            'Membro' => esc_html( $d['display_name'] ?? '' ),
            'E-mail' => '<a href="mailto:' . esc_attr( $d['user_email'] ?? '' ) . '" style="color:#5a6c7d;">' . esc_html( $d['user_email'] ?? '' ) . '</a>',
        ];
        if ( ! empty( $d['membership_level_name'] ) ) {
            $rows['Plano'] = esc_html( $d['membership_level_name'] );
        }
        if ( ! empty( $d['order_id'] ) ) {
            $pedido = '#!!order_id!!';
            if ( ! empty( $d['order_date'] ) ) {
                $pedido .= ' · !!order_date!!';
            }
            $rows['Pedido'] = $pedido;
        }
        if ( $with_total && ! empty( $d['order_total'] ) ) {
            $rows['Total'] = '!!order_total!!';
        }
        if ( ! empty( $d['startdate'] ) ) {
            $rows['Início'] = '!!startdate!!';
        }
        if ( ! empty( $d['enddate'] ) ) {
            $rows['Acesso até'] = '!!enddate!!';
        }
        return $this->details( $rows );
    }
}

add_action( 'plugins_loaded', function () {
    BBL_PMPro_Emails::get_instance();
} );
