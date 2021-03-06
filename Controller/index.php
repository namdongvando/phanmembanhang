<?php

use Model\UsersService;

class Controller_index extends Application {

    public $param;
    public $ViewTheme;
    public $Pages;
    public $News;

    function __construct() {
        $this->param = $this->getParam();
        $this->Pages = new \Model\pages();
        $this->News = new \Model\news();
        Model_ViewTheme::set_viewthene("home");
    }

    function index() {
        Model_Seo::$Title = "__Title___";
        Model_Seo::$des = "__Des___";
        Model_Seo::$key = "__Keyword___";
        $this->ViewTheme("", Model_ViewTheme::get_viewthene(), "");
    }

    function sanpham() {

        Model_Seo::$Title = "Sản Phẩm";
        Model_Seo::$des = "{SEO_Des}";
        Model_Seo::$key = "{SEO_Keyword}";
        $bre = new Model\Breadcrumb();
        $abre[] = [
            "link" => "#"
            , "title" => "Sản Phẩm"
        ];

        $bre->setBreadcrumb($abre);
        $this->ViewTheme([], Model_ViewTheme::get_viewthene(), "danhmuc");
    }

    function ctsanpham() {

        $_GET["alias"] = lib\Common::CheckInput($_GET["alias"]);
        $mp = new Model\Products();
        $p = $mp->ProductsByAlias($_GET["alias"], FALSE);
        if ($p == null) {
            header("HTTP/1.0 404 Not Found");
        }
        $data["p"] = $p;

        $p["Views"] = $p["Views"] + 1;
        $mp->EditProducts($p);
        $p = new Model\Products($p);
        Model_Seo::$Title = $p->nameProduct;
        Model_Seo::$des = $p->Summary;
        Model_Seo::$key = $p->Summary;
        $this->ViewTheme($data, Model_ViewTheme::get_viewthene(), "product");
    }

    function syspage($url) {
        $Category = new Model\Category();
//        var_dump($url);
        $this->ViewTheme($data, Model_ViewTheme::get_viewthene(), "danhmuc");
    }

    function category($url) {

        $Category = new Model\Category();
//        lấy danh ra
        $linkDanhMuc = $url[1][0];
        $pathCat = $Category->getCategoryFromPath($linkDanhMuc);
        $catCurent = $Category->Category4Path($pathCat);
        $bre = new Model\Breadcrumb();
        $abre = $Category->Breadcrumb($catCurent->catID);
        $bre->setBreadcrumb($abre);
        $Pages = isset($url[2]) ? $url[2][0] : 1;
        $data["Category"] = $catCurent;
        $data["Pages"] = $Pages;

        Model_Seo::$Title = $catCurent->catName;
        Model_Seo::$des = "{SEO_Des}";
        Model_Seo::$key = "{SEO_Keyword}";
        Model\Breadcrumb::$Title = $catCurent->catName;
        $this->ViewTheme($data, Model_ViewTheme::get_viewthene(), "danhmuc");
    }

    function news($url) {
        $aliasPages = $url[1][0];
        $aliasNews = $url[2][0];
        $Page = $this->Pages->PagesByAlias($aliasPages, FALSE);
        $_Page = new \Model\pages($Page);
        $news = $this->News->NewsByAlias($aliasNews, $_Page->idPa);
        $_News = new \Model\news($news);
        $_Breadcrumb = new \Model\Breadcrumb();

        $a = [
            [
                "title" => $_Page->Name,
                "link" => $_Page->linkPagesCurent()
            ],
            [
                "title" => $_News->Name,
                "link" => $_News->linkNewsCurent()
            ]
        ];
        $_Breadcrumb->setBreadcrumb($a);
        $data["news"] = $news;
        $data["pages"] = $Page;
        Model_Seo::$Title = $_News->Name;
        Model_Seo::$des = $_News->description;
        Model_Seo::$key = $_News->keyword;
        $this->ViewTheme($data, Model_ViewTheme::get_viewthene(), "danhmuc");
    }

    function pages($url) {
        $pages = new \Model\pages();
        $_Pages = $pages->PagesByAlias($url[1][0], FALSE);
        $Pages = new \Model\pages($_Pages);
        Model_Seo::$Title = $Pages->Title;
        Model_Seo::$des = $Pages->Des;
        Model_Seo::$key = $Pages->Keyword;
        $data["pages"] = $_Pages;

        Model\Breadcrumb::$Title = $Pages->Name;
        $bre = new Model\Breadcrumb();
        $abre = $pages->Breadcrumb($Pages->idPa);
        $bre->setBreadcrumb($abre);

        $this->ViewTheme($data, Model_ViewTheme::get_viewthene(), "danhmuc");
    }

    function product($url) {

        $mp = new Model\Products();
        $p = $mp->ProductsByAlias($url[2][0], FALSE);
        $data["p"] = $p;

        $p["Views"] = $p["Views"] + 1;
        $mp->EditProducts($p);
        $p = new Model\Products($p);
        Model_Seo::$Title = $p->nameProduct;
        Model_Seo::$des = $p->Summary;
        Model_Seo::$key = $p->Summary;
        $this->ViewTheme($data, Model_ViewTheme::get_viewthene(), "product");
    }

    function pagesdetail($url) {

        $pages = new \Model\pages();
        $_Pages = $pages->PagesByAlias($url[1][0], FALSE);
        $Pages = new \Model\pages($_Pages);
        Model_Seo::$Title = $Pages->Title;
        Model_Seo::$des = $Pages->Des;
        Model_Seo::$key = $Pages->Keyword;
        $data["pages"] = $_Pages;

        $bre = new Model\Breadcrumb();
        $abre = $pages->Breadcrumb($Pages->idPa);
        $bre->setBreadcrumb($abre);
        Model\Breadcrumb::$Title = $Pages->Name;
        $this->ViewTheme($data, Model_ViewTheme::get_viewthene(), "danhmuc");
    }

    function syspagedetail($Url) {
        $data["Page"] = $this->Pages->TimPages4TieuDeKD($Url[1][0]);
        $p = new Model_Pages($data["Page"]);
        Model_Seo::$Title = $p->Title;
        Model_Seo::$des = $p->Des;
        Model_Seo::$key = $p->Keyword;
        $this->ViewTheme($data, Model_ViewTheme::get_viewthene(), "danhmuc");
    }

    function login() {

        $_SESSION[UserLogin] = isset($_SESSION[UserLogin]) ? $_SESSION[UserLogin] : null;
        if ($_SESSION[UserLogin]) {
            Common\Common::toUrl("/profile/");
        }
        $_SESSION["DangKy"] = isset($_SESSION["DangKy"]) ? $_SESSION["DangKy"] : \lib\guid::guidV4();
//        var_dump($_SESSION["DangKy"]);
        Model\FormLogin::setFormName($_SESSION["DangKy"]);
        if (isset($_POST[$_SESSION["DangKy"]])) {
            try {
                $FormLogin = $_POST[$_SESSION["DangKy"]];
                $FormLogin["Username"] = Model\CheckInput::Input($FormLogin["Username"]);
                $FormLogin["Password"] = Model\CheckInput::Input($FormLogin["Password"]);
                $modelUser = \Model\AdminService::CheckLogin($FormLogin);
                if ($modelUser) {
                    unset($modelUser["Password"]);
                    $_SESSION[UserLogin] = $modelUser;
                }
                if ($_SESSION[UserLogin]) {
                    Common\Common::toUrl("/profile/index/");
                }
            } catch (Exception $exc) {
                Model\Error::set($exc->getMessage(), Model\Error::Danger);
            }
        }
        $bre = new Model\Breadcrumb();
        $abre = $bre->setBreadcrumb([["title" => "Đăng Nhập", "link" => "#"]]);
        $this->ViewTheme("", Model_ViewTheme::get_viewthene(), "login");
    }

    function logout() {
        $_SESSION[UserLogin] = null;
        Common\Common::toUrl("/profile/index");
        exit();
    }

}
?>

