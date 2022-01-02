<?php

namespace Laravel\Bridge\Pub\Controller;

use XF\Mvc\ParameterBag;

class Bridge extends \XF\Pub\Controller\AbstractController
{
    const FORUM_HMAC_SECRET = '';

    public function actionIndex(ParameterBag $params)
    {
        if ($token = $this->filter('token', 'str')) {
            return $this->doLogin($token);
        }

        die('error');
    }

    public function actionLogout(ParameterBag $params)
    {
        $this->session()->logoutUser();
        return $this->redirect('https://zeslecp.com/forums/logout');
    }

    public function actionLogin(ParameterBag $params)
    {
        return $this->redirect('https://zeslecp.com/forums/login');
    }

    public function actionRegister(ParameterBag $params)
    {
        return $this->redirect('https://zeslecp.com/forums/register');
    }

    public function actionLostPassword(ParameterBag $params)
    {
        return $this->redirect('https://zeslecp.com/forums/lost-password');
    }

    private function verifySig(string $bridge_token)
    {
        $data = (string)base64_decode($bridge_token);
        parse_str($data, $output);

        $sign = $output['sign'] ?? 'sign';
        $email = $output['email'] ?? 'email';

        if (hash_equals($sign, \hash_hmac('sha256', $email, self::FORUM_HMAC_SECRET))) {
            return $email;
        }

        return false;
    }

    private function doLogin(string $bridge_token)
    {
        $ip = $this->request->getIp();
        $session = $this->session();

        $user = null;
        if ($email = $this->verifySig($bridge_token)) {
            $finder = \XF::finder('XF:User');
            /** @var \XF\Entity\User $user */
            $user = $finder->where('email', $email)->fetchOne();
        }

        if (!$user) {
            return $this->redirect('/?not_found');
        }

        if ($user->user_id !== \XF::visitor()->user_id) {
            $session->changeUser($user);
            \XF::setVisitor($user);
        }

        $this->repository('XF:SessionActivity')->clearUserActivity(0, $ip);

        $this->repository('XF:Ip')->logIp(
            $user->user_id, $ip,
            'user', $user->user_id, 'login'
        );

        return $this->redirect('/');
    }

    /**
     * POST disable CSRF
     */
    public function checkCsrfIfNeeded($action, ParameterBag $params)
    {
        if ($action == 'Test') {
            return;
        }

        parent::checkCsrfIfNeeded($action, $params);
    }
}
