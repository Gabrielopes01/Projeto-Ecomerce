<?php

namespace Classes\Model;

use \Classes\DB\Sql;
use \Classes\Model;
use \Classes\Mailer;

class User extends Model{

    const SESSION = "User";
    const SECRET = "HcodePhp7_Secret";
    const SECRET_IV = "senhasenha123456";
    const ERROR = "UserError";
    const ERROR_REGISTER = "UserErrorRegister";
    const SUCESS = "UserSucess";

//Pegar valores da pessoa
    public static function getPersonValues($user, $iduser){

        $sql = new Sql();

        $result = $sql->select("
            SELECT b.idperson, b.desperson, b.desemail, b.nrphone
            FROM tb_users a
            INNER JOIN tb_persons b ON a.idperson = b.idperson
            WHERE a.iduser = :iduser", [
                ":iduser"=>$iduser
            ]
        );

        $user->setData($result[0]);

    }


    public static function getFromSession(){

        $user = new User();

        if (isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]["iduser"] > 0){

            $user->setData($_SESSION[User::SESSION]);

        }

        return $user;

    }


    public static function checkLogin($inadmin = true){

        if (
            !isset($_SESSION[User::SESSION])
            ||
            !$_SESSION[User::SESSION]
            ||
            !(int)$_SESSION[User::SESSION]["iduser"] > 0)
        {
            //Nao esta logado
            return false;

        } else {

            if ($inadmin === true && (bool)$_SESSION[User::SESSION]["inadmin"] === true){

                return true;

            } else if ($inadmin === false){

                return true;

            } else {

                return false;

            }

        }

    }


    public static function login($login, $password)
    {

        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(
            ":LOGIN" => $login
        ));

        if (count($results) === 0){

            throw new \Exception("Usuário Inexistente ou Senha Inválida", 1);

        }

        $data = $results[0];

        if (password_verify($password, $data["despassword"]) === true)
        {

            $user = new User();

            //$data["deslogin"] = utf8_encode($data["deslogin"]);

            $user->setData($data);

            $_SESSION[User::SESSION] = $user->getValues();

            return $user;

        } else {

            throw new \Exception("Usuário Inexistente ou Senha Inválida", 1);

        }

    }


    public static function verifyLogin($inadmin = true)
    {

        if (!User::checkLogin($inadmin)) {

            if ($inadmin) {
                header("Location: /admin/login");
            } else {
                header("Location: /login");
            }
            exit;

        }

    }

    public static function logout()
    {

        $_SESSION[User::SESSION] = NULL;

    }

    public static function listAll(){

        $sql = new Sql();

        return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");

    }

    public function save(){

        $sql = new Sql();

        $results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
            ":desperson"=>$this->getdesperson(),
            ":deslogin"=>$this->getdeslogin(),
            ":despassword"=>User::getPasswordHash($this->getdespassword()),
            ":desemail"=>$this->getdesemail(),
            ":nrphone"=>$this->getnrphone(),
            ":inadmin"=>$this->getinadmin()
        ));

        //$results[0]["deslogin"] = utf8_encode($results[0]["deslogin"]);

        $this->setData($results[0]);

    }


    public function get($iduser){

        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser", array(
            ":iduser"=>$iduser
        ));

        $this->setData($results[0]);

    }


    public function update(){

        $sql = new Sql();

        $results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
            ":iduser"=>$this->getiduser(),
            ":desperson"=>$this->getdesperson(),
            ":deslogin"=>$this->getdeslogin(),
            ":despassword"=>User::getPasswordHash($this->getdespassword()),
            ":desemail"=>$this->getdesemail(),
            ":nrphone"=>$this->getnrphone(),
            ":inadmin"=>$this->getinadmin()
        ));

        $this->setData($results[0]);

    }

    public function delete(){

        $sql = new Sql();

        $sql->query("CALL sp_users_delete(:iduser)", array(
            ":iduser"=>$this->getiduser()
        ));

    }


//Fuçoes Email
    public static function getForgot($email, $inadmin = true){

        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_persons a INNER JOIN tb_users b USING(idperson) WHERE a.desemail = :email", array(
            ":email" => $email
        ));

        if(count($results) === 0){
            throw new \Exception("Não foi possível recuperar a Senha", 1);

        } else {

            $data = $results[0];

            $results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
                ":iduser" => $data["iduser"],
                ":desip" => $_SERVER["REMOTE_ADDR"]
            ));

            if(count($results2) === 0){

                throw new \Exception("Não foi possível recuperar a Senha", 1);

            } else {

                $dataRecovery = $results2[0];

                $code = base64_encode(openssl_encrypt($dataRecovery["idrecovery"], "AES-128-CBC", User::SECRET, 0, User::SECRET_IV));

                if ($inadmin === true){
                    $link = "http://www.projectcommerce.com.br/admin/forgot/reset?code=$code";
                }else{
                    $link = "http://www.projectcommerce.com.br/forgot/reset?code=$code";
                }

                //Enviando Email
                $mailer = new Mailer($data["desemail"], $data["desperson"], "Redefinir Senha do Ecomerce Store", "forgot", array(
                    "name"=>$data["desperson"],
                    "link"=>$link
                ));

                $mailer->send();

                return $data;

            }

        }

    }



//Validar codigo do email
    public static function validForgotDecrypt($code)
    {

        $idrecovery = openssl_decrypt(base64_decode($code), "AES-128-CBC", User::SECRET, 0, User::SECRET_IV);

        $sql = new Sql();

        $results = $sql->select("
            SELECT *
            FROM tb_userspasswordsrecoveries a
            INNER JOIN tb_users b USING (iduser)
            INNER JOIN tb_persons c USING (idperson)
            WHERE
            a.idrecovery = :idrecovery
            AND
            a.dtrecovery IS NULL
            AND
            DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW()
        ", array(
            ":idrecovery"=>$idrecovery
        ));


        if (count($results) === 0)
        {

            throw new \Exception("Não foi possível recuperar a senha", 1);

        } else {

            return $results[0];

        }

    }


//Definindo que o link de redefinição foi usado
    public static function setForgotUsed($idrecovery)
    {

        $sql = new Sql();

        $sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery", array(
            ":idrecovery"=>$idrecovery
        ));

    }

//Função que altera a senha
    public static function setPassword($password)
    {

        $sql = new Sql();

        $sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array(
            ":password"=>$password,
            ":iduser"=>$this->getiduser()
        ));

    }

    public static function setError($msg){

        $_SESSION[User::ERROR] = $msg;

    }

    public static function getError(){

        $msg = (isset($_SESSION[User::ERROR]) && $_SESSION[User::ERROR]) ? $_SESSION[User::ERROR] : "";

        User::clearError();

        return $msg;

    }

    public static function clearError(){

        $_SESSION[User::ERROR] = NULL;

    }


    public static function getPasswordHash($password){

        return password_hash($password, PASSWORD_DEFAULT,[
            "cost"=>12
        ]);

    }


//Erro de registro de usuario
    public static function setErrorRegister($msg){

        $_SESSION[User::ERROR_REGISTER] = $msg;

    }


    public static function getErrorRegister(){

        $msg = (isset($_SESSION[User::ERROR_REGISTER]) && $_SESSION[User::ERROR_REGISTER]) ? $_SESSION[User::ERROR_REGISTER] : "";

        User::clearErrorRegister();

        return $msg;

    }


    public static function clearErrorRegister(){

        $_SESSION[User::ERROR_REGISTER] = NULL;

    }



    public static function checkLoginExists($login){

        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :deslogin", [
            ":deslogin"=>$login
        ]);

        return (count($results) > 0);

    }


    public static function setSucess($msg){

        $_SESSION[User::SUCESS] = $msg;

    }

    public static function getSucess(){

        $msg = (isset($_SESSION[User::SUCESS]) && $_SESSION[User::SUCESS]) ? $_SESSION[User::SUCESS] : "";

        User::clearSucess();

        return $msg;

    }

    public static function clearSucess(){

        $_SESSION[User::SUCESS] = NULL;

    }


    public function getOrders(){

        $sql = new Sql();

        $results = $sql->select("
            SELECT *
            FROM tb_orders a
            INNER JOIN tb_ordersstatus b USING(idstatus)
            INNER JOIN tb_carts c USING(idcart)
            INNER JOIN tb_users d ON d.iduser = a.iduser
            INNER JOIN tb_addresses e USING(idaddress)
            INNER JOIN tb_persons f ON f.idperson = d.idperson
            WHERE a.iduser = :iduser", [
                ":iduser"=>$this->getiduser()
            ]);

        return $results;

    }


    public static function getPage($page = 1, $itemsPerPage = 10){

        $start = ($page - 1) * $itemsPerPage;

        $sql = new Sql();

        $results = $sql->select("
            SELECT SQL_CALC_FOUND_ROWS *
            FROM tb_users a
            INNER JOIN tb_persons b USING(idperson)
            ORDER BY b.desperson
            LIMIT $start, $itemsPerPage
        ");

        $resultTotal = $sql->select("SELECT FOUND_ROWS() AS TOTAL");

        return [
            "data"=>$results,
            "total"=>(int)$resultTotal[0]["TOTAL"],
            "pages"=>ceil($resultTotal[0]["TOTAL"] / $itemsPerPage)
        ];

    }


    public static function getPageSearch($search, $page = 1, $itemsPerPage = 10){

        $start = ($page - 1) * $itemsPerPage;

        $sql = new Sql();

        $results = $sql->select("
            SELECT SQL_CALC_FOUND_ROWS *
            FROM tb_users a
            INNER JOIN tb_persons b USING(idperson)
            WHERE b.desperson LIKE :search OR b.desemail = :search OR a.deslogin LIKE :search
            ORDER BY b.desperson
            LIMIT $start, $itemsPerPage
        ", [
            ":search"=>"%".$search."%"
        ]);

        $resultTotal = $sql->select("SELECT FOUND_ROWS() AS TOTAL");

        return [
            "data"=>$results,
            "total"=>(int)$resultTotal[0]["TOTAL"],
            "pages"=>ceil($resultTotal[0]["TOTAL"] / $itemsPerPage)
        ];

    }



}

?>