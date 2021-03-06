<?php

use Module\duser\Model\Duser;
use Module\cart\Model\OrderService;
use Module\cart\Model\Order;

class Controller_apibe extends Controller_backend {

    function __construct() {
        parent::__construct();
    }

    function chuyenDonHang() {
        try {
            $id = \Model\CheckInput::Input($_POST["orderCode"]);
            $userName = \Model\CheckInput::Input($_POST["Username"]);
            $modelOrder = new Order($id);
            if ($modelOrder->ChekStauts([Order::DaNopTienVeCty,
                        Order::DaThuTien,
                        Order::DangXuLy,
                        Order::ThanhCong]) == true) {
                throw new Exception("Đơn Hàng Đã Xử lÝ/ Thu Tiền Không thể Chuyển");
            }
            $DUser = new Duser($userName);
            if ($DUser->Username == null) {
                throw new Exception("Không có Tài khoản đích");
            }
            $modelOrder->Saler = $DUser->Username;
            $OrderArray = $modelOrder->ToArrayMode();
            $OrderArray["Saler"] = $DUser->Username;
            $modelOrder->updateOrder($OrderArray);
            $data["Mes"] = "Đã Chuyển Đơn Hàng Thành Công";
        } catch (Exception $exc) {
            $data["Mes"] = $exc->getMessage();
        }
        lib\APIs::Json_Encode_ToString($data);
    }

    function timnhanvien() {
        $admin = new \Model\AdminService();
        $total = 0;
        $indexPage = 1;
        $pageNumber = 10;
        $dataList = $admin->GetAllPT($this->getParam()[0], $total, $indexPage, $pageNumber);
        $data["data"] = $dataList;
        $data["total"] = $dataList;
        $data["pageIndex"] = $indexPage;
        $data["pageNumber"] = $pageNumber;
        $data["params"] = [
            "totalPages" => ceil($total / $pageNumber),
            "total" => $total,
            "pageIndex" => $indexPage,
            "pageNumber" => $pageNumber,
        ];
        lib\APIs::Json_Encode_ToString($data);
    }

    function userGroups() {
        $user = new \Module\duser\Model\Duser();
        $groups = $user->getGroupsAll();
        echo lib\APIs::Json_Encode($groups);
    }

    function XacNhanDonHang() {
        try {
            $Order = new \Module\cart\Model\Order();
            $_Order = $Order->orderbyid($_POST["Id"]);
            $_Order = $_Order[0];
            if ($_Order["Status"] == \Module\cart\Model\Order::DaThuTien || $_Order["Status"] == \Module\cart\Model\Order::DaNopTienVeCty) {
                throw new Exception("Đơn Hàng Đã Nhận Tiền. Không Thể Cập Nhật");
            }
            $_Order["Status"] = \Module\cart\Model\Order::XacNhanDonHang;
            $Order->updateOrder($_Order);
            $saler = \Module\duser\Model\Duser::CurentUsernameAdmin(true)->Username;
            $content = "[{$saler}] Xác Nhận Đơn Hàng";
            \Module\cart\Model\OrderLog::CreateLog($_Order["Status"], "Đã Xác Nhận Đơn Hàng", $content, $_Order["Id"], $saler);
            $data["Mes"] = "Đã Xác Nhận Đơn Hàng";
            echo lib\APIs::Json_Encode($data);
        } catch (Exception $exc) {
            $data["Mes"] = $exc->getMessage();
            echo lib\APIs::Json_Encode($data);
        }
    }

    function DataThuTienDonHang() {
        try {
            $Order = new \Module\cart\Model\Order();
            $_Order = $Order->orderbyid($_POST["Id"]);
            $_Order = $_Order[0];
            if ($_Order["Status"] == \Module\cart\Model\Order::DaNopTienVeCty) {
                throw new Exception("Đơn Hàng Đã Nộp Tiền Về Công Ty. Không Thể Cập Nhật");
            }
            $_Order["Status"] = \Module\cart\Model\Order::DaThuTien;
            $Order->updateOrder($_Order);
            $data["Mes"] = "Đã Thu Tiền Đơn Hàng";
            echo lib\APIs::Json_Encode($data);
        } catch (Exception $exc) {
            $data["Mes"] = $exc->getMessage();
            echo lib\APIs::Json_Encode($data);
        }
    }

    function DaNopTienVeCty() {
        try {
            if (Duser::KiemTraQuyen([Duser::Superadmin, Duser::admin, Duser::QuanLyDonHang]) == FALSE) {
                return;
            }
            $Order = new \Module\cart\Model\Order();
            $_Order = $Order->orderbyid($_POST["Id"]);
            $_Order = $_Order[0];
            if ($_Order["Status"] == \Module\cart\Model\Order::DaNopTienVeCty) {
                throw new Exception("Đã Nộp Tiền Về Công Ty. Liên hệ Cty để cập nhật Đơn Hàng");
            }
            $_Order["Status"] = \Module\cart\Model\Order::DaNopTienVeCty;
            $Order->updateOrder($_Order);
            $data["Mes"] = "Đã Nộp Tiền Về Công Ty.";
            echo lib\APIs::Json_Encode($data);
        } catch (Exception $exc) {
            $data["Mes"] = $exc->getMessage();
            echo lib\APIs::Json_Encode($data);
        }
    }

    function HuyDonHang() {
        try {
            $Order = new \Module\cart\Model\Order();
            $_Order = $Order->orderbyid($_POST["Id"]);
            $_Order = $_Order[0];
            $LyDo = \Model\CheckInput::Input($_POST["LyDo"]);
            if ($_Order["Status"] == \Module\cart\Model\Order::DaThuTien || $_Order["Status"] == \Module\cart\Model\Order::DaNopTienVeCty) {
                throw new Exception("Đơn Hàng Đã Nhận Tiền Không Thể Hủy");
            }
            $_Order["Status"] = \Module\cart\Model\Order::Huy;
            $_Order["Note"] = "[" . $_SESSION[QuanTri]["Username"] . "] Hủy đơn hàng :" . date("Y-m-d H:i:s") . " <br>" . $LyDo . "<br>_______<br>" . $_Order["Note"];
            $Order->updateOrder($_Order);
            $data["Mes"] = "Đã Hủy Đơn Hàng";
            echo lib\APIs::Json_Encode($data);
        } catch (Exception $exc) {
            $data["Mes"] = $exc->getMessage();
            echo lib\APIs::Json_Encode($data);
        }
    }

    function getOrderDetail() {
        $orderId = $this->getParam()[0];
        $order = new \Module\cart\Model\Order();
        $orderDetail = $order->orderbyid($orderId);
        if ($orderDetail == FALSE) {
            return null;
        }
        $orderDetail = $orderDetail[0];
        $_order = new \Module\cart\Model\Order($orderDetail);
        $orderDetail = $_order->ToArray();
        $orderDetail["StatusName"] = $_order->Status();
        $dsSanPham = $_order->ProductsByDonHang();
        if ($dsSanPham) {
            foreach ($dsSanPham as $k => $value) {
                $prod = new \Model\Products($value["IdProduct"]);
                $value["ThanhTien"] = $value["Number"] * $value["Price"];
                $value["PriceVND"] = \lib\Common::MoneyFomat($value["Price"]);
                $value["ThanhTienVND"] = \lib\Common::MoneyFomat($value["Number"] * $value["Price"]);
                $value["ProductDetail"] = $prod->Obj2Api();
                $dsSanPham[$k] = $value;
            }
        }
        $orderDetail["TotalPriceVND"] = \lib\Common::MoneyFomat($orderDetail["TotalPrice"]);
        $orderDetail["Products"] = $dsSanPham;
        $orderDetail["SalerInfor"] = $_order->Saler()->ToArray();
        echo lib\APIs::Json_Encode($orderDetail);
    }

    function OrderStatus() {
        $order = new \Module\cart\Model\Order();
        echo lib\APIs::Json_Encode($order->listStatusAll());
    }

    function saveInformationCustomer() {
        try {
            $khachHang = $_POST["KhachHang"];
            $kh = new \Module\duser\KhachHang\KhachHang($khachHang);
            $khdetail = $kh->GetByPhone($khachHang["Phone"]);
            if ($khdetail == null) {
                $kh->Post($khachHang);
            } else {
                $khdetail['Name'] = $khachHang["Name"];
                $khdetail['Phone'] = $khachHang["Phone"];
                $khdetail['Email'] = $khachHang["Email"];
                $khdetail['DiaChi'] = $khachHang["DiaChi"];
                $khdetail['Tinh'] = $khachHang["Tinh"];
                $khdetail['Huyen'] = $khachHang["Huyen"];
                $kh->Put($khdetail);
            }
            $data["Mes"] = "Đã Lưu Thông Tin Khách Hàng";
            echo lib\APIs::Json_Encode($data);
        } catch (Exception $exc) {
            $data["Mes"] = "Đã Có Thông Tin Khách Hàng Này";
            echo lib\APIs::Json_Encode($data);
        }
    }

    function getuserInformation() {
        $phone = $this->getParam()[0];
        $khachHang = new \Module\duser\KhachHang\KhachHang();
        $khachHangDetail = $khachHang->GetByPhone($phone);
        if ($khachHangDetail) {
            echo lib\APIs::Json_Encode($khachHangDetail);
        } else {
            echo lib\APIs::Json_Encode(["Phone" => $phone]);
        }
    }

    function getcarts() {
        $cart = new \Module\cart\Model\Cart();
        $data["Product"] = $cart->Products();
        foreach ($data["Product"] as $k => $value) {
            $_p = new \Model\Products($value);
            $data["Product"][$k]["linkProduct"] = $_p->linkProduct();
            $data["Product"][$k]["ThanhTien"] = $cart->ThanhTien($value["Number"], $value["Price"]);
            $data["Product"][$k]["ThanhTienVND"] = $cart->ThanhTienVND($value["Number"], $value["Price"]);
            $data["Product"][$k]["priceVnd"] = $_p->Price();
        }
        $data["TotalPriceVND"] = $cart->TotalPriceVND();
        $data["TotalPrice"] = $cart->TotalPrice();
        $data["SumProduct"] = $cart->TongSanPham();
        $lib = new \lib\APIs();
        $lib->ArrayToApi($data);
    }

    function GetAllCategory() {
        $a = new \Model\Category();
        $danhMuc = [];
//        var_dump($danhMuc);
        $a->GetAllCategory(0, $danhMuc, null, array(0 => "0", 1 => "1"));
        foreach ($danhMuc as $v) {
            echo $v["catName"] . "<br>";
        }
    }

    function getProductsByName() {
        $SanPham = new Model\Products();
        $name = $this->getParam()[0];
        $Page = 1;
        $Number = 20;
        $dataProducts = null;
        if ($name != "") {
            $dataProducts = $SanPham->ProductsNameAllPT($name, $Page, $Number, $Tong);
        }
        $data["Tong"] = $Tong;
        $data["Name"] = $name;
        if ($dataProducts)
            foreach ($dataProducts as $k => $product) {
                $_product = new Model\Products($product);
                $dataProducts[$k] = $_product->Obj2Api();
            }
        $data["Products"] = $dataProducts;
        echo \lib\APIs::Json_Encode($data);
    }

    function GetProductById() {
        $id = $this->getParam()[0];
        $product = new Model\Products($id);
        $array = $product->Obj2Api();
        echo $product->_encode($array);
    }

    function productStatus() {
        header("Content-type: application/json; charset=utf-8");
        $SanPham = new Model\Products();
        echo $SanPham->_encode(Model\Products::status());
    }

    function SaveProduct() {
        $SanPham = new Model\Products();
        $product_detail = $SanPham->ProductsByID($_POST["ID"], false);
        $product_detail["Number"] = intval($_POST["Number"]);
        $product_detail["Code"] = $_POST["Code"];
        $product_detail["Price"] = intval($_POST["Price"]);
        $product_detail["GiaNhap"] = intval($_POST["GiaNhap"]);
        $product_detail["isShow"] = intval($_POST["isShow"]);
        $product_detail["nameProduct"] = $_POST["nameProduct"];
        $SanPham->EditProducts($product_detail);
    }

    function productCat() {
        $a = new \Model\Category();
        $danhMuc = [];
//        var_dump($danhMuc);
        $a->GetAllCategory(0, $danhMuc, null, array(0 => "0", 1 => "1"));
        echo $a->_encode($danhMuc);
    }

    function stringCode() {
        return str_pad(92, 4, '0', STR_PAD_LEFT);
    }

    function resetCode() {
        $product = new Model\Products();
        $ps = $product->ProductsAll();
        $ps = array_map(function($item) {
            $Cat = new Model\Category($item["catID"]);
            $item["Code"] = $Cat->Code . lib\Common::addZeroToNumber($item["ID"], 4);
            return $item;
        }, $ps);
        $product->EditCodeProducts($ps);
    }

    function GetOrdersByUserName() {
        $saler = \Module\duser\Model\Duser::CurentUsernameAdmin(true)->Username;
        $order = new \Module\cart\Model\Order();
        $total = 0;
        $indexPage = isset($this->getParam()[0]) ? intval($this->getParam()[0]) : 1;
        $pageNumber = isset($this->getParam()[1]) ? intval($this->getParam()[1]) : 10;
        $data["Params"]["Status"] = $_POST["Status"];
        $data["Params"]["Saler"] = $saler;
        $data["Params"]["Keyword"] = \Model\CheckInput::Input($_POST["Keyword"]);
        $orderBySaler = $order->GetBySale($data["Params"], $total, $indexPage, $pageNumber);
        if ($orderBySaler)
            foreach ($orderBySaler as $k => $order) {
                $_order = new Module\cart\Model\Order($order["CodeOrder"]);
                $order = $_order->ToArray();
                $orderBySaler[$k] = $order;
            }
        $data["total"] = intval($total);
        $data["indexPage"] = $indexPage;
        $data["pageNumber"] = $pageNumber;
        $data["totalPage"] = ceil($data["total"] / $data["pageNumber"]);
        $data["data"] = $orderBySaler;
        echo lib\APIs::Json_Encode($data);
    }

    function GetAllOrders() {
        $saler = \Module\duser\Model\Duser::CurentUsernameAdmin(true)->Username;
        $order = new \Module\cart\Model\Order();
        $total = 0;
        $indexPage = isset($this->getParam()[0]) ? intval($this->getParam()[0]) : 1;
        $pageNumber = isset($this->getParam()[1]) ? intval($this->getParam()[1]) : 10;
        $data["Params"]["Status"] = $_POST["Status"];
        $data["Params"]["Keyword"] = \Model\CheckInput::Input($_POST["Keyword"]);
        $orderBySaler = $order->GetBySale($data["Params"], $total, $indexPage, $pageNumber);
        if ($orderBySaler)
            foreach ($orderBySaler as $k => $order) {
                $_order = new Module\cart\Model\Order($order["CodeOrder"]);
                $order = $_order->ToArray();
                $orderBySaler[$k] = $order;
            }
        $data["total"] = intval($total);
        $data["indexPage"] = $indexPage;
        $data["pageNumber"] = $pageNumber;
        $data["totalPage"] = ceil($data["total"] / $data["pageNumber"]);
        $data["data"] = $orderBySaler;
        echo lib\APIs::Json_Encode($data);
    }

}

?>
