<?
/*
  EMR65 - Mid-week Updates to DTI
  -------------------------------------------------
  AUTHOR                  :   FSTPL
  CREATED FOR             :   Teak Systems Incorporated
  (c) COPYRIGHT 2018-19 Teak Systems Incorporated
  =================================================================
  SCREEN                  :   EMR65
  DESCRIPTION             :   Send Mid-week Updates to DTI
  CALLED FROM             :   SE021
  ERROR FILE CODE         :   05131801 (EMR65)
 */
?>
<?

##################
#	HEADERS:
##################
ini_set("max_execution_time", "0");
?>
<?

include('../cm/mysql_connection.php');
?>
<?

list($scriptName, $dataBase, $clientId, $clientLocId, $strvar) = $argv;


if ($scriptName == '' || $dataBase == '' || $clientId == '' || $clientLocId == '' || $strvar == '') {
    print "Missing Information..........";
    exit;
}
// FOR CLIENT COMPANY AND LOCATION
$ASCompanyID = $clientId;
$ASLocationID = $clientLocId;

// THIS IS THE DATABASE TO ACCESS.
$currDate = date('Y-m-d H:i:s');

// THIS IS THE CONNECTION WHICH WILL BE FETCHED FROM THE FILE
$databaseInfoArr = getDatabaseDetails($dataBase);

$strServer = $databaseInfoArr['strServer'];
$strTempUsr = $databaseInfoArr['strUserName'];
$strTempPwd = $databaseInfoArr['strPassword'];
/* 1.4 Starts */
$strPort = $databaseInfoArr['strPort'];

$ARSstrvar = trim($strvar);
$twochar = substr(trim($ARSstrvar), 3, 2);
$twochar = trim($twochar);

$cvtconnection = @((($GLOBALS["___mysqli_ston"] = mysqli_init()) && (mysqli_real_connect($GLOBALS["___mysqli_ston"], $strServer, $strTempUsr, $strTempPwd, NULL, $strPort, NULL, 128))) ? $GLOBALS["___mysqli_ston"] : FALSE);
/* 1.4 Ends */

if ($cvtconnection) {

    // SELECT DATABASE
    mysqli_select_db($cvtconnection, $dataBase);
    $db = $dataBase;

    // FOR DEBUG
    $qryGetInfo = "SELECT charvar chrVar
                        FROM system
                        WHERE recid = 'AAAAA'";

    $rsltGetInfo = mysqli_query($cvtconnection, $qryGetInfo)or die("Error");
    if (!$rsltGetInfo) {
        $fileLineNbr = __LINE__;
        $strErrorCode = getErrorCode(19, 01);
        print("\n<span class=\"dataerror\">Error Occurred. ErrorCode: " . $strErrorCode . "</span>\n\n\n");
        unset($fileLineNbr);
    } else {
        if (mysqli_num_rows($rsltGetInfo) == 0) {
            $debug = false;
        } else {
            $rowGetInfo = mysqli_fetch_assoc($rsltGetInfo);
            if ($rowGetInfo[chrVar] == "Y") {
                $debug = true;
            } else {
                $debug = false;
            }
        }
    }

    if ($debug)
        print("<b>Databass = $db, UserName = $strTempUsr, Password = $strTempPwd, Server = $strServer</b>\n\n");


    if ($debug)
        print("<b>Curr Date = $currDate</b>\n\n");

    // INCLUDE FILES HERE
    include("../cm/csv.php");
    include("../cm/commonfunc.php");
    include("../cm/getinsqry.php");
    include('../cm/htmlMimeMailEx.php');
    include("../rd/specificdraw.php");
    include_once('../cm/archivefunc.php');
    /* 1.4 Starts */
    include('../rd/tradatefunc.php');
    /* 1.4 Ends */
    $debug = 1;

    // GETTING LocationSystem ARI - FOR FLAG TO PROCESS EMAIL PREVIEWS
    $ARDstrvar = "ARD" . $twochar;
    $ARDstrvar = trim($ARDstrvar);

    // GETTING LocationSystem ARDLA - FOR EMAILS ADDRESS AND NOTIFY GROUP
    $str_ARD = GetLSVar($ARDstrvar, "strvar", $ASLocationID, $cvtconnection);
    if ($debug)
        print("ARI = $str_ARD\n\n");

    $arrayARD = explode('|', $str_ARD);

    $char_ARS = GetLSVar($ARSstrvar, "charvar", $ASLocationID, $cvtconnection);
    if ($debug)
        print("char_ARD = $char_ARS\n\n");

    // GETTING LocationSystem ARSXX - FOR NEXT SCHEDULED EMAIL PREVIEW
    $str_ARSXX = GetLSVar($ARSstrvar, "strvar", $ASLocationID, $cvtconnection);
    #if ($str_ARSXX != "2099-01-01 00:00:00") {
    #    $str_ARSXX = date('Y-m-d H:i:s', strtotime($str_ARSL));
    #}
    if ($debug)
        print("str_ARSXX = $str_ARSXX\n\n");

    // GETTING LocationSystem ARPXX - FOR NEWS & MESSENGER PREVIEW
    $ARPstrvar = "ARP" . $twochar;
    $ARPstrvar = trim($ARPstrvar);

    $str_ARPXX = GetLSVar($ARPstrvar, "strvar", $ASLocationID, $cvtconnection);
    if ($debug)
        print("ARPXX = $str_ARPXX\n\n");

    $char_ARPXX = GetLSVar($ARPstrvar, "charvar", $ASLocationID, $cvtconnection);
    if ($debug)
        print("char_ARPXX = $char_ARPXX\n\n");

    ##to support the request from new client "Press Democrat"
    $char_3MODE = GetLSVar("3MODE", "charvar", $ASLocationID, $cvtconnection);
    if ($debug)
        print("3MODE = $char_3MODE\n\n");


    $val_SPLIT = GetCSVar("SPLIT", "strvar", $ASCompanyID, $cvtconnection);
    if ($debug)
        print("SPLIT = $val_SPLIT\n");
    $GLOBALS[val_SPLIT] = $val_SPLIT;

    $tmpARPXX = explode('~', $str_ARPXX);

    $arrayARPXX = array();
    while (list($key, $strVal) = each($tmpARPXX)) {
        $tmpProdArr = explode("~", $strVal);

        $prodArray = array(
            "PRODDESC" => $tmpProdArr[0]
        );
        array_push($arrayARPXX, $prodArray);
    }


    $str_ARPXX = trim($str_ARPXX);
    $str = substr($str_ARPXX, -1);
    $cntRecords = count($arrayARPXX);

    if ($str == "|") {
        $cntRecords = $cntRecords - 1;
    }

    if ($debug) {
        print("<pre style=\"text-align: left\">");
        print("arrayARPXX = ");
        print_r($arrayARPXX);
        print("</pre>\n");
    }

    // GETTING LocationSystem ARPLA - FOR EMAILS SUBJECT
    $sDesc_ARPXX = GetLSVar($ARPstrvar, "sdesc", $ASLocationID, $cvtconnection);
    if ($debug)
        print("ARPLA = $sDesc_ARPXX\n\n");

    // GETTING System EMLID - FOR DEFAULT EMAIL
    $val_EMLID = GetLSVar("EMLID", "strvar", $ASLocationID, $cvtconnection);
    if ($debug)
        print("EMLID = $val_EMLID\n\n");
    if ($val_EMLID == "") {
        $val_EMLID = "noreply@teakwce.com";
    }

    // GETTING LocationSystem EMLNM - FOR Email Sender Name
    $str_EMLNM = GetLSVar("EMLNM", "strvar", $ASLocationID, $cvtconnection);
    if ($debug)
        print("EMLNM= $str_EMLNM\n\n");

    $ARXstr = "ARX" . $twochar;
    $str_ARX = GetLSVar($ARXstr, "strvar", $ASLocationID, $cvtconnection);
    if ($debug)
        print("str_ARX = $str_ARX\n");


    $char_ARX = GetLSVar($ARXstr, "charvar", $ASLocationID, $cvtconnection);
    if ($char_ARX == "") {
        $char_ARX = "A";
    }
    if ($debug)
        print("char_ARX = $char_ARX\n");

    if ($str_ARX != '') {

        $str_ARX = str_replace("~", ",", $str_ARX);
        $IncTab = ", demolocation dl ";
        $IncWhere = " AND l.recid= dl.locationid  AND dl.demographicvalueid IN ($str_ARX) /*AND (dl.endeffdt IS NULL OR dl.endeffdt = '0000-00-00' AND dl.endeffdt > now())*/ ";
    }

    $datevar_ARXXX = GetLSVar("$ARXstr", "datevar", $ASLocationID, $cvtconnection);
    if ($debug)
        print("run date = $datevar_ARXXX\n");


    // GETTING LocationSystem TODTI - FOR Barcode
    $val_TODTI = GetLSVar("TODTI", "charvar", $ASLocationID, $cvtconnection);
    if ($debug)
        print("TODTI = $val_TODTI\n\n");

    // GETTING LocationSystem VMCPY - FOR VENDING COMPANY
    $val_VMCPY = GetLSVar("VMCPY", "intvar", $ASLocationID, $cvtconnection);
    if ($debug)
        print("VMCPY = $val_VMCPY\n\n");


    // GETTING LocationSystem 1FILR - FOR VENDING COMPANY
    $val_1FILR = GetLSVar("1FILR", "charvar", $ASLocationID, $cvtconnection);
    if ($debug)
        print("1FILR = $val_1FILR\n\n");

    //Getting Locationsystem 3FSCO
    $int_3FSCO = GetLSVar("3FSCO", "intvar", $ASLocationID, $cvtconnection);
    if (!is_numeric($int_3FSCO))
        $int_3FSCO = 0;
    if ($debug)
        print("3FSCO = $int_3FSCO\n\n");

    #to trigger CP59 to keep report on client FTP.
    $char_FTP59 = GetLSVar("FTP59", "charvar", $ASLocationID, $cvtconnection);
    if ($debug)
        print("FTP59 = $char_FTP59\n");

    print "\n$str_ARSXX < $currDate\n";
    if (($str_ARSXX < $currDate) && ($char_ARS == "S" || $char_ARS == "Y")) {
        $qryUp1 = "UPDATE locationsystem
		  SET strvar = '2099-01-01 00:00:00'
		  WHERE recid = '$ARSstrvar'
		  AND locationid = '$ASLocationID' ";
        if ($debug)
            print("<b>" . $qryUp1 . "</b><br />\n");
        $rsltUp1 = mysqli_query($cvtconnection, $qryUp1);
        if (!$rsltUp1) {
            $fileLineNbr = __LINE__;
            $strErrorCode = getErrorCode(19, 03);
            print("<br /><span class=\"dataerror\">Error Occurred. ErrorCode: " . $strErrorCode . "</span><br /><br />\n");
            unset($fileLineNbr);
        } else {
            if ($debug)
                print("<b>" . mysqli_affected_rows($cvtconnection) . "</b> affected.<br />\n");
        }
        //TEMPORARY
        $qryDroptemp = "DROP TEMPORARY TABLE IF EXISTS tmp_emr65";
        $reslt_Droptemp = mysqli_query($cvtconnection, $qryDroptemp);
        if (!$reslt_Droptemp) {
            $fileLineNbr = __LINE__;
            $strErrorCode = getErrorCode(01, 01);
            print("\n<span class=\"dataerror\">Error Occurred. ErrorCode: " . $strErrorCode . "</span>\n\n\n");
            unset($fileLineNbr);
        }
        /* 1.4 Starts */
        $qryCreateTempTable = "CREATE /*emr65*/ TEMPORARY TABLE IF NOT EXISTS tmp_emr65(
                                locationid BIGINT( 40 ),
                                rtcode varchar(50),
                                pubid BIGINT( 40 ),
                                pubcode  varchar( 10 ),
                                difftype varchar( 3 ),
                                taqty INT( 50 ),
                                dtiqty INT( 50 ),
                                diffqty INT( 50 ),
                                barcode varchar( 25 ),
                                pubdate date, 
                                KEY tmp_idex (locationid), 
                                KEY tmp_idex_rtcode (rtcode), 
                                KEY tmp_idex_pubcode (pubcode))";
        /* 1.4 Ends */
        mysqli_query($cvtconnection, $qryCreateTempTable)or die("Error");

        $qryDroptemp = "DROP TEMPORARY TABLE IF EXISTS tmp_location_emr65";
        $reslt_Droptemp = mysqli_query($cvtconnection, $qryDroptemp);
        if (!$reslt_Droptemp) {
            $fileLineNbr = __LINE__;
            $strErrorCode = getErrorCode(01, 01);
            print("\n<span class=\"dataerror\">Error Occurred. ErrorCode: " . $strErrorCode . "</span>\n\n\n");
            unset($fileLineNbr);
        }

        /* 1.4 Starts */
        $qryCreateLocTable = "CREATE /*emr65*/ TEMPORARY TABLE IF NOT EXISTS tmp_location_emr65(
                                    locationid BIGINT( 40 ) NOT NULL,
                                    barcode varchar( 25 ) NOT NULL,
                                    UNIQUE KEY tmp_idex (locationid))";
        mysqli_query($cvtconnection, $qryCreateLocTable) or die("Error in $qryCreateLocTable\r\n");


        $qryDroptemp = "DROP /*emr65*/ TEMPORARY TABLE IF EXISTS tmp_prod_emr65";
        $reslt_Droptemp = mysqli_query($cvtconnection, $qryDroptemp);
        if (!$reslt_Droptemp) {
            $fileLineNbr = __LINE__;
            $strErrorCode = getErrorCode(01, 01);
            print("\n<span class=\"dataerror\">Error Occurred. ErrorCode: " . $strErrorCode . "</span>\n\n\n");
            unset($fileLineNbr);
        }
        //Task#8785 comment -> added daycodeday column
        $qryCreateProdTable = "CREATE /*emr65*/ TEMPORARY TABLE IF NOT EXISTS tmp_prod_emr65(
                                prodid INT( 40 ) NOT NULL,
                                spid BIGINT( 40 ) NOT NULL,
                                spdatex DATE NOT NULL ,
                                daycodeday varchar(21) NOT NULL DEFAULT '',
                                KEY tmp_idex (prodid), 
                                KEY tmp_idex_rtcode (spid), 
                                KEY tmp_idex_pubcode (spdatex))";
        mysqli_query($cvtconnection, $qryCreateProdTable) or die("Error in $qryCreateProdTable\r\n");
        /* 1.4 Ends */

        //Find Product that needs to be included in the report
        $ProductId = "";
        $prodPiArr = array();
        $prodreFecArr = array();
        $ProductId = '';
        for ($j = 0; $j < $cntRecords; $j++) {
            $prodDesc = $arrayARPXX[$j][PRODDESC];

            $qryGetProductId = "SELECT cm.twcerecid PID
                                FROM companymap cm 
                                WHERE cm.companyvalue = '$prodDesc'
                                AND cm.clientlocid = $ASLocationID
                                AND cm.fieldcode = 'PI'
                                AND (endeffdt = '0000-00-00' OR endeffdt IS NULL OR endeffdt > now()) ";
            echo $qryGetProductId . "\n";
            $rsltGetProductId = mysqli_query($cvtconnection, $qryGetProductId)or die("Error");
            if (!$rsltGetProductId) {
                $fileLineNbr = __LINE__;
                $strErrorCode = getErrorCode(01, 01);
                print("\n<span class=\"dataerror\">Error Occurred. ErrorCode: " . $strErrorCode . "</span>\n\n\n");
                unset($fileLineNbr);
            } else {
                while ($rowGetProductId = mysqli_fetch_assoc($rsltGetProductId)) {
                    if ($ProductId == '') {
                        $ProductId = $rowGetProductId[PID];
                    } else {
                        $ProductId .= "," . $rowGetProductId[PID];
                    }
                    $prodPiArr[$rowGetProductId[PID]] = $prodDesc;
                }
            }
        }//for($j = 0 ; $j < $cntRecords ; $j++ )
        #echo '<pre>';print_r($prodPiArr);

        if (trim($ProductId) != "") {
            //To get the retail location
            $locStr = "";
            if ($char_ARX == "D") {
                $IncWhere .= " AND l.type = 'D' ";
            } else if ($char_ARX == "B") {
                $IncWhere .= " AND l.type IN ('D', 'M') ";
            } else if ($char_ARX == "R") {
                $IncWhere .= " AND l.type IN ('D', 'R') ";
            }

            /* 1.4 Starts */
            $qryGetLoc = "SELECT /*emr65*/ DISTINCT(l.recid) locid, rl.barcode
                            FROM location l, locationlink ll, customerlink cl, routelink rl $IncTab
                            WHERE cl.clientid = '$ASCompanyID'
                            AND cl.clientlocid =  '$ASLocationID'
                            AND ll.companyid = cl.customerid
                            AND ll.locationid = l.recid
                            AND l.recid = rl.locationid
                            AND rl.clientid = '$ASCompanyID'
                            $IncWhere";
            if ($val_VMCPY != '' && ($char_ARX == 'B' || $char_ARX == 'A')) {
                $qryGetLoc .= " UNION SELECT DISTINCT(l.recid) locid, rl.barcode
                                    FROM location l , locationlink ll, routelink rl $IncTab
                                    WHERE ll.companyid = '$val_VMCPY'
                                    AND ll.locationid = l.recid
                                    AND l.recid = rl.locationid
                                    AND rl.clientid = '$ASCompanyID'
                                    $IncWhere ";
            }
            if ($debug)
                echo $qryGetLoc;
            $rsltGetLoc = mysqli_query($cvtconnection, $qryGetLoc)or die("Error");
            $tempSpRecIdArr = array();
            if (!$rsltGetLoc) {
                $fileLineNbr = __LINE__;
                $strErrorCode = getErrorCode(01, 01);
                print("\n<span class=\"dataerror\">Error Occurred. ErrorCode: " . $strErrorCode . "</span>\n\n\n");
                unset($fileLineNbr);
            } else {
                if (mysqli_num_rows($rsltGetLoc) != 0) {
                    while ($rowGetLoc = mysqli_fetch_assoc($rsltGetLoc)) {
                        if ($locStr == '') {
                            $locStr = "('" . $rowGetLoc[locid] . "', '" . $rowGetLoc[barcode] . "')";
                        } else {
                            $locStr .= ",('" . $rowGetLoc[locid] . "', '" . $rowGetLoc[barcode] . "')";
                        }
                    }//While END
                }//IF End
            }//Else END


            $InsQry = "INSERT /*emr65*/ INTO tmp_location_emr65 (locationid, barcode) VALUES $locStr";
            if ($debug)
                print "$InsQry\n";
            mysqli_query($cvtconnection, $InsQry)or die("Error");


            ###TEMP PRODUCT TABLE
            $prodStr = "";
            //Task#8785 comment -> added 1. FROM table "daycode dc" and 2. WHERE "AND p.daycodeid = dc.recid"
            $qryGetProd = "SELECT /*emr65*/ DISTINCT(p.recid) prodid, sp.recid spid, sp.datex spdatex, dc.day
                            FROM product p, specificproduct sp, transactivity ta, tmp_location_emr65 tmpLoc, daycode dc
                            WHERE p.recid IN (" . $ProductId . ")
                            AND p.clientid = '$ASCompanyID'
                            AND sp.productid = p.recid
                            AND p.daycodeid = dc.recid
                            AND ta.locationid = '$ASLocationID'
                            AND ta.type = 'SD'
                            AND ta.customerlocid = tmpLoc.locationid
                            AND ta.datet = '$datevar_ARXXX'
                            AND ta.specificproductid = sp.recid
                            AND (sp.endeffdt IS NULL OR sp.endeffdt = '0000-00-00' OR sp.endeffdt > now()) ";
            $arrInfo_ARCHIVE = array(
                "arrLookup" => array(
                    "specificproduct" => array(
                        "datex" => array(
                            "dbFromDate" => $datevar_ARXXX,
                            "dbToDate" => $datevar_ARXXX
                        )
                    )
                )
            );
            #$debug=1;
            $rsltGetProd = getQryResultSELECT($qryGetProd, $arrInfo_ARCHIVE);
            #$debug=0;
            if (!$rsltGetProd) {
                $fileLineNbr = __LINE__;
                $strErrorCode = getErrorCode(01, 01);
                print("\n<span class=\"dataerror\">Error Occurred. ErrorCode: " . $strErrorCode . "</span>\n\n\n");
                unset($fileLineNbr);
            } else {
                if (mysqli_num_rows($rsltGetProd) != 0) {
                    while ($rowGetProd = mysqli_fetch_assoc($rsltGetProd)) {
                        if ($prodStr == '') {
                            $prodStr = "('" . $rowGetProd[prodid] . "', '" . $rowGetProd[spid] . "', '" . $rowGetProd[spdatex] . "', '" . $rowGetProd[day] . "')";//Task#8785
                        } else {
                            $prodStr .= ",('" . $rowGetProd[prodid] . "', '" . $rowGetProd[spid] . "', '" . $rowGetProd[spdatex] . "', '" . $rowGetProd[day] . "')";//Task#8785
                        }
                    }
                }
            }
            if($prodStr != "") {
                $InsQry = "INSERT /*emr65*/ INTO tmp_prod_emr65 (prodid, spid, spdatex, daycodeday) VALUES $prodStr";//Task#8785
                if ($debug)
                    print "$InsQry\n";
                mysqli_query($cvtconnection, $InsQry)or die("Error");
            }


            /* $qryGetInfoTemp = "SELECT tmpLoc.locationid CustLocId, ta.customerlocid TrCustLocId,
              dti.locationid dtiCustLocId, p.recid ProdId,
              SUM(ta.actquantity) SDQty, sp.datex spDate,
              sp.recid SpRecId, rl.barcode, SUM(dti.quantity) DTIQty,
              dti.publication, dti.dtirouteid
              FROM (product p, routelink rl, tmp_location_emr65 tmpLoc)
              LEFT JOIN
              (transactivity ta,specificproduct sp)
              ON ta.locationid = '$ASLocationID'
              AND ta.type = 'SD'
              AND ta.customerlocid = tmpLoc.locationid
              AND ta.datet = '$datevar_ARXXX'
              AND ta.specificproductid = sp.recid
              AND sp.productid IN (" . $ProductId . ")
              AND sp.productid = p.recid
              AND p.clientid = '$ASCompanyID'
              LEFT JOIN
              dtidraw dti
              ON dti.locationid = tmpLoc.locationid
              AND dti.productid = p.recid
              AND dti.dated = '$datevar_ARXXX'
              AND dti.active = 'Y'
              WHERE tmpLoc.locationid > 0
              AND p.recid IN (" . $ProductId . ")
              AND p.recid is NOT NULL
              AND rl.clientid = '$ASCompanyID'
              AND rl.locationid = tmpLoc.locationid
              GROUP BY tmpLoc.locationid, SpRecId, p.recid
              HAVING  (SDQty > 0 OR DTIQty > 0)
              "; */
            //Task#8785 comment -> fetch tmpProd.daycodeday in below query
            $qryGetInfoTemp = "SELECT /*emr65*/ tmpLoc.locationid CustLocId, ta.customerlocid TrCustLocId,
                                dti.locationid dtiCustLocId, tmpProd.prodid ProdId, 
                                tmpProd.daycodeday, 
                                SUM(ta.actquantity) SDQty, tmpProd.spdatex spDate, 
                                tmpProd.spid SpRecId, tmpLoc.barcode, SUM(dti.quantity) DTIQty, 
                                dti.publication, dti.dtirouteid
                                FROM (tmp_prod_emr65 tmpProd, tmp_location_emr65 tmpLoc) 
                                LEFT JOIN 
                                    transactivity ta
                                    ON ta.locationid = '$ASLocationID'
                                    AND ta.type = 'SD'
                                    AND ta.customerlocid = tmpLoc.locationid
                                    AND ta.datet = '$datevar_ARXXX'
                                    AND ta.specificproductid = tmpProd.spid
                                LEFT JOIN 
                                    dtidraw dti
                                    ON dti.locationid = tmpLoc.locationid
                                    AND dti.productid = tmpProd.prodid
                                    AND dti.dated = '$datevar_ARXXX'
                                    AND dti.active = 'Y' 
                                WHERE tmpLoc.locationid > 0 
                                GROUP BY tmpLoc.locationid, SpRecId, tmpProd.prodid
                                HAVING  (SDQty > 0 OR DTIQty > 0) 
                                ";
            /* 1.4 Ends */
            $arrInfo_ARCHIVE = array(
                "arrLookup" => array(
                    "specificproduct" => array(
                        "datex" => array(
                            "dbFromDate" => $datevar_ARXXX,
                            "dbToDate" => $datevar_ARXXX
                        )
                    )
                )
            );
            #$debug=1;
            $rsltGetInfoTemp = getQryResultSELECT($qryGetInfoTemp, $arrInfo_ARCHIVE);
            #$debug=0;
            if (!$rsltGetInfoTemp) {
                $fileLineNbr = __LINE__;
                $strErrorCode = getErrorCode(14, 01);
                print("\n<span class=\"dataerror\">Error Occurred. ErrorCode: " . $strErrorCode . "</span>\n\n\n");
                unset($fileLineNbr);
            } else {
                while ($rowGetInfoTemp = mysqli_fetch_assoc($rsltGetInfoTemp)) {
                    $locationId = $rowGetInfoTemp[CustLocId];
                    $SDQty = $rowGetInfoTemp[SDQty];
                    if (!is_numeric($SDQty))
                        $SDQty = 0;
                    $pubid = $rowGetInfoTemp[ProdId];
                    $daycodeday = $rowGetInfoTemp[daycodeday];//Task#8785
                    $SpDate = $rowGetInfoTemp[spDate];
                    $SpRecId = $rowGetInfoTemp[SpRecId];
                    $Barcode = $rowGetInfoTemp[barcode];
                    $prodCode = $prodPiArr[$pubid];

                    $DTIQty = $rowGetInfoTemp[DTIQty];
                    if (!is_numeric($DTIQty))
                        $DTIQty = 0;

                    $dti_publication = $rowGetInfoTemp[publication];
                    $dtirouteid = $rowGetInfoTemp[dtirouteid];

                    $RouteCode = '';
                    $qryGetPubCode = "SELECT companyvalue
                                        FROM companymap cm
                                        WHERE cm.clientid = '$ASCompanyID'
                                        AND cm.companyid = '$int_3FSCO'
                                        AND cm.fieldcode = 'RC'
                                        AND cm.twcerecid = '$locationId'
                                        AND cm.companyvalue LIKE '!$prodCode%'
                                        AND (endeffdt = '0000-00-00' OR endeffdt IS NULL OR endeffdt > now())
                                        GROUP BY cm.twcerecid ";
                    echo "$qryGetPubCode\r\n";
                    $rsltGetPubCod = mysqli_query($cvtconnection, $qryGetPubCode);
                    $tempSpRecIdArr = array();
                    if (!$rsltGetPubCod) {
                        $fileLineNbr = __LINE__;
                        $strErrorCode = getErrorCode(01, 01);
                        print("\n<span class=\"dataerror\">Error Occurred. ErrorCode: " . $strErrorCode . "</span>\n\n\n");
                        unset($fileLineNbr);
                    } else {
                        if (mysqli_num_rows($rsltGetPubCod) > 0) {
                            $rowCode = mysqli_fetch_assoc($rsltGetPubCod);
                            $RouteCode = $rowCode[companyvalue];
                        } else {
                            $qry2 = "SELECT cm.companyvalue
                                        FROM companymap cm
                                        WHERE cm.clientid = '$ASCompanyID'
                                        AND cm.companyid = '$int_3FSCO'
                                        AND cm.fieldcode = 'RC'
                                        AND cm.twcerecid = '$locationId'
                                        AND cm.companyvalue NOT LIKE '!%'
                                        AND (endeffdt = '0000-00-00' OR endeffdt IS NULL OR endeffdt > now())
                                        GROUP BY cm.twcerecid ";
                            echo "$qry2\r\n";
                            $rsltGet2 = mysqli_query($cvtconnection, $qry2);
                            if (!$rsltGet2) {
                                $fileLineNbr = __LINE__;
                                $strErrorCode = getErrorCode(01, 01);
                                print("\n<span class=\"dataerror\">Error Occurred. ErrorCode: " . $strErrorCode . "</span>\n\n\n");
                                unset($fileLineNbr);
                            } else {
                                $rowCode2 = mysqli_fetch_assoc($rsltGet2);
                                $RouteCode = $rowCode2[companyvalue];
                            }
                        }
                    }
                    print "RouteCode: $RouteCode\r\n";

                    /* 1.4 Starts */
                    $dtiCustLocId = $rowGetInfoTemp[dtiCustLocId];
                    if (!is_numeric($dtiCustLocId))
                        $dtiCustLocId = 0;

                    //Task#8785 comment -> fetched "datex" and added  "ORDER BY datex DESC LIMIT 1" in below query
                    $qryGet_leadLag = "SELECT /*emr65*/ day, leadlag, datex
                                            FROM stddrawleadlag
                                            WHERE customerlocid = '$locationId'
                                            AND productid = '$pubid'
                                            ORDER BY datex DESC LIMIT 1 ";
                    if ($debug)
                        echo "$qryGet_leadLag\r\n";
                    $rslGet_leadLag = mysqli_query($cvtconnection, $qryGet_leadLag);
                    if (!$rslGet_leadLag) {
                        $fileLineNbr = __LINE__;
                        $strErrorCode = getErrorCode(19, 03);
                        print("<br /><span class=\"dataerror\">Error Occurred. ErrorCode: " . $strErrorCode . "</span><br /><br />\n");
                        unset($fileLineNbr);
                    }
                    if (mysqli_num_rows($rslGet_leadLag) > 0) {
                        $rowGet_leadLag = mysqli_fetch_assoc($rslGet_leadLag);
                        $ll_Day = strtoupper($rowGet_leadLag[day]);
                        $leadLag = $rowGet_leadLag[leadlag];
                        //Task#8785 Starts
                        $lldatex = $rowGet_leadLag[datex];
						
                        $lead_lag_flag = 0;
                        if($daycodeday == 'IND') {
                            if($lldatex) {
                                $tempDate = $lldatex;
                            }
                            else {
                                if ($leadLag < 0) {
                                    $tempDate = DateOperations_TRADATE($datevar_ARXXX, ($leadLag * -1), "ADD");
                                } else {
                                    $tempDate = DateOperations_TRADATE($datevar_ARXXX, $leadLag, "SUB");
                                }
                            }
                            if($tempDate == $SpDate){
                                $lead_lag_flag = 1;
                            }
                        }//END if($daycodeday == 'IND')
                        else {
                            if ($leadLag < 0) {
                                $tempDate = DateOperations_TRADATE($datevar_ARXXX, ($leadLag * -1), "ADD");
                            } else {
                                $tempDate = DateOperations_TRADATE($datevar_ARXXX, $leadLag, "SUB");
                            }

                            $dow_tempDate = strtoupper(date("D", mktime(0, 0, 0, substr($tempDate, 5, 2), substr($tempDate, 8, 2), substr($tempDate, 0, 4))));

                            if ($dow_tempDate == $ll_Day) {
                                $lead_lag_flag = 1;
                            }
                        }
                        //if ($dow_tempDate == $ll_Day) {Task#8785 comment 
			if ($lead_lag_flag) {
                            $qry_DTI = "SELECT /*emr65*/ quantity, publication, dtirouteid
                                        FROM dtidraw 
                                        WHERE clientlocid = '$ASLocationID'
                                        AND productid = '$pubid'
                                        AND locationid = '$locationId'
                                        AND dated = '$tempDate'
                                        AND active = 'Y' ";
                            if ($debug)
                                echo "$qry_DTI\r\n";
                            $rsl_DTI = mysqli_query($cvtconnection, $qry_DTI);
                            if (!$rsl_DTI) {
                                $fileLineNbr = __LINE__;
                                $strErrorCode = getErrorCode(19, 03);
                                print("<br /><span class=\"dataerror\">Error Occurred. ErrorCode: " . $strErrorCode . "</span><br /><br />\n");
                                unset($fileLineNbr);
                            }
                            if (mysqli_num_rows($rsl_DTI) > 0) {
                                $row_DTI = mysqli_fetch_assoc($rsl_DTI);
                                $DTIQty = $row_DTI[quantity];
                                if (!is_numeric($DTIQty))
                                    $DTIQty = 0;
                                $dti_publication = $row_DTI[publication];
                                $dtirouteid = $row_DTI[dtirouteid];

                                $SpDate = $tempDate;
                            }
                        }//END if ($lead_lag_flag)
                        //Task#8785 Ends
                        else {
                            continue;
                        }
                    }//END if (mysqli_num_rows($rslGet_leadLag) > 0)

                    $DiffQty = 0;
                    $DiffQty = $SDQty - $DTIQty;
                    if ($debug) {
                        echo "SDQty: $SDQty - DTIQty: $DTIQty\r\n";
                        echo "DiffQty: $DiffQty\r\n";
                    }
                    /* 1.4 Ends */

                    if ($DiffQty != 0) {
                        if ($RouteCode != "" || $val_TODTI == 'A') {
                            ####UPDATE DTIDRAW STARTS
                            /* 1.4 Starts */
                            $check_DTI = "SELECT /*emr65*/ recid dti_N_Id
                                            FROM dtidraw 
                                            WHERE clientlocid = '$ASLocationID'
                                            AND productid = '$pubid'
                                            AND locationid = '$locationId'
                                            AND dated = '$SpDate'
                                            AND active = 'N' ";
                            /* 1.4 Ends */
                            if ($debug)
                                print "\n" . $check_DTI . "\n";
                            $rsl_check_DTI = mysqli_query($GLOBALS["___mysqli_ston"], $check_DTI);
                            if (!$rsl_check_DTI) {
                                $fileLineNbr = __LINE__;
                                $strErrorCode = getErrorCode(01, 01);
                                print("\n<span class=\"dataerror\">Error Occurred. ErrorCode: " . $strErrorCode . "</span>\n\n\n");
                                unset($fileLineNbr);
                            } else {
                                if (mysqli_num_rows($rsl_check_DTI) == 0) {
                                    //update current record to N and create 'Y'
                                    /* 1.4 Starts */
                                    $qryUp1 = "UPDATE /*emr65*/ dtidraw
                                                SET active = 'N'
                                                WHERE clientlocid = '$ASLocationID'
                                                AND productid = '$pubid'
                                                AND locationid = '$locationId'
                                                AND dated = '$SpDate'
                                                AND active = 'Y' ";
                                    /* 1.4 Ends */
                                    if ($debug)
                                        print("<b>" . $qryUp1 . "</b>\r\n");
                                    $rsltUp1 = mysqli_query($cvtconnection, $qryUp1);
                                    if (!$rsltUp1) {
                                        $fileLineNbr = __LINE__;
                                        $strErrorCode = getErrorCode(19, 03);
                                        print("<br /><span class=\"dataerror\">Error Occurred. ErrorCode: " . $strErrorCode . "</span><br /><br />\n");
                                        unset($fileLineNbr);
                                    }
                                    /* 1.4 Starts */
                                    $q_dtidraw = "INSERT /*emr65*/ INTO dtidraw
                                                (clientlocid, publication, productid, dated, 
                                                dtirouteid, locationid, quantity, active)
                                                VALUES
                                                ('$ASLocationID', '$dti_publication', '$pubid',
                                                '$SpDate', '$dtirouteid', '$locationId', '$SDQty', 'Y')";
                                    /* 1.4 Ends */
                                    if ($debug)
                                        print("$q_dtidraw\n");
                                    $r_dtidraw = mysqli_query($cvtconnection, $q_dtidraw);
                                    if (!$r_dtidraw) {
                                        $fileLineNbr = __LINE__;
                                        $strErrorCode = getErrorCode(12, 01);
                                        print("<br /><span class=\"dataerror\">Error Occurred. ErrorCode: " . $strErrorCode . "</span><br /><br />\n");
                                        unset($fileLineNbr);
                                    }
                                } else {
                                    //update current record with new quantity
                                    /* 1.4 Starts */
                                    $qryUp1 = "UPDATE /*emr65*/ dtidraw
                                                SET quantity = '$SDQty'
                                                WHERE clientlocid = '$ASLocationID'
                                                AND productid = '$pubid'
                                                AND locationid = '$locationId'
                                                AND dated = '$SpDate'
                                                AND active = 'Y' ";
                                    /* 1.4 Ends */
                                    if ($debug)
                                        print("<b>" . $qryUp1 . "</b>\r\n");
                                    $rsltUp1 = mysqli_query($cvtconnection, $qryUp1);
                                    if (!$rsltUp1) {
                                        $fileLineNbr = __LINE__;
                                        $strErrorCode = getErrorCode(19, 03);
                                        print("<br /><span class=\"dataerror\">Error Occurred. ErrorCode: " . $strErrorCode . "</span><br /><br />\n");
                                        unset($fileLineNbr);
                                    }
                                }
                            }
                        }##END if($RouteCode != "" || $val_TODTI == 'A')
                        ####UPDATE DTIDRAW ENDS

                        if ($DiffQty < 0) {
                            $DiffType = 'Cut';
                            $DiffQty = $DiffQty;
                        } else if ($DiffQty > 0) {
                            $DiffType = 'Add';
                        } else {
                            $DiffType = 'No';
                            $DiffQty = 0;
                        }
                        print "\n\nDiffQty:$DiffQty\n";
                        if ($DiffQty != 0) {
                            /* 1.4 Starts */
                            $InsertDataIntoTemp = "INSERT /*emr65*/ INTO tmp_emr65(locationid, rtcode, pubid, pubcode, pubdate, difftype, taqty, dtiqty, diffqty, barcode) VALUES ('$locationId', '$RouteCode', '$pubid', '$prodCode', '$SpDate', '$DiffType', '$SDQty', '$DTIQty', '$DiffQty', '$Barcode')";
                            /* 1.4 Ends */
                            if ($debug)
                                print "$InsertDataIntoTemp\n";
                            $insTemp1 = mysqli_query($cvtconnection, $InsertDataIntoTemp) or die("Error in InsertDataIntoTemp:$InsertDataIntoTemp");
                        }
                    }//END if($DiffQty != 0)
                }
            }
        }
        #echo "Insert Complete\r\n";
        #exit;

        $FileArr = array();
        $dirPath = '';
        $dirPath = "/usr/local/twceconf/emr/emr65/" . $ASLocationID . "";
        #echo "dirPath: $dirPath\r\n";
        $folderList = explode("/", $dirPath);
        $lenFolder = count($folderList);
        for ($cntFolder = 0; $cntFolder < $lenFolder; $cntFolder++) {
            $tmpFolder .= $folderList[$cntFolder] . '/';
            if (!file_exists($tmpFolder))
                mkdir($tmpFolder);
        }

        #######
        if ($val_1FILR == "M") {
            $FileArr = fn_create_file_logic($val_TODTI, "Y", $char_3MODE, $char_ARPXX, $datevar_ARXXX, "Y", $dirPath, $FileArr, $debug, $cvtconnection);

            $FileArr = fn_create_file_logic($val_TODTI, "M", $char_3MODE, $char_ARPXX, $datevar_ARXXX, "Y", $dirPath, $FileArr, $debug, $cvtconnection);
        } else {
            $FileArr = fn_create_file_logic($val_TODTI, $val_1FILR, $char_3MODE, $char_ARPXX, $datevar_ARXXX, "N", $dirPath, $FileArr, $debug, $cvtconnection);
        }
        ######

        if ($val_TODTI != 'A') {
            ##Report for companyMap Not set
            $qryGetTempCnt = "SELECT count(*) tmpCnt FROM tmp_emr65 WHERE rtcode = '' ";
            if ($debug)
                print $qryGetTempCnt . "\n";
            $rsltGetTempCnt = mysqli_query($cvtconnection, $qryGetTempCnt)or die("Error");
            $rowGetTempCnt = mysqli_fetch_assoc($rsltGetTempCnt);
            $tmpCnt = $rowGetTempCnt[tmpCnt];

            //HTML FILE CREATION
            if ($tmpCnt != 0) {
                $AdjuNotFoundStr = '';
                $qryGetInfo = "SELECT * FROM tmp_emr65 tm WHERE rtcode = '' ORDER BY barcode,pubdate";
                if ($debug)
                    print $qryGetInfo . "\n";
                $rsltGetInfo = mysqli_query($GLOBALS["___mysqli_ston"], $qryGetInfo);
                if (!$rsltGetInfo) {
                    $fileLineNbr = __LINE__;
                    $strErrorCode = getErrorCode(14, 01);
                    print("\n<span class=\"dataerror\">Error Occurred. ErrorCode: " . $strErrorCode . "</span>\n\n\n");
                    unset($fileLineNbr);
                } else {
                    while ($rowGetInfo = mysqli_fetch_assoc($rsltGetInfo)) {
                        $Barcode = $rowGetInfo[barcode];
                        $pubDate = $rowGetInfo[pubdate];
                        $pubDate = date("m/d/y", strtotime($datevar_ARXXX));
                        $difftype = $rowGetInfo[difftype];
                        $diffqty = $rowGetInfo[diffqty];
                        $taqty = $rowGetInfo[taqty];
                        if ($char_ARPXX == "F") {
                            $newqty = $taqty;
                        } else {
                            $newqty = $diffqty;
                        }
                        $AdjuNotFoundStr .= "$Barcode,$newqty,$pubDate\n";
                    }
                }
            }
        }


        ####################################
        ######EMAIL LOGIC#####################
        #######################################
        print_r($FileArr);
        if (count($FileArr) > 0 || strlen($AdjuNotFoundStr) > 0) {
            $eMailStr = "";
            // NOW FINDING TMS GROUP PERSON FOR SENDING DRAW REQUEST MANAGEMENT REPORT
            $qryGetPersonInfo = "SELECT p.email eMailStr
                                    FROM notifyperson np, notifygrouplink ngl, personlink pl, person p
                                    WHERE ngl.locationid = $ASLocationID ";
            $qryGetPersonInfo .= " AND ngl.groupcode = '$arrayARD[1]' ";
            $qryGetPersonInfo .= "AND ngl.groupcode = np.groupcode
                                    AND np.personlinkid = pl.recid
                                    AND pl.locationid = '$ASLocationID'
                                    AND p.recid = pl.personid";

            if ($debug)
                print($qryGetPersonInfo . "\n\n");
            $rsltGetPersonInfo = mysqli_query($cvtconnection, $qryGetPersonInfo)or die("Error");
            if (!$rsltGetPersonInfo) {
                $fileLineNbr = __LINE__;
                $strErrorCode = getErrorCode(19, 01);
                print("\n<span class=\"dataerror\">Error Occurred. ErrorCode: " . $strErrorCode . "</span>\n\n\n");
                unset($fileLineNbr);
            } else {
                if (mysqli_num_rows($rsltGetPersonInfo) == 0) {
                    
                } else {
                    while ($rowGetPersonInfo = mysqli_fetch_assoc($rsltGetPersonInfo)) {
                        if ($eMailStr == "") {
                            $eMailStr = $rowGetPersonInfo[eMailStr];
                        } else {
                            $eMailStr .= "," . $rowGetPersonInfo[eMailStr];
                        }
                    }
                }
            }

            if ($eMailStr == "") {
                $eMailStr = $arrayARD[2];
            } else {
                $eMailStr .= "," . $arrayARD[2];
            }

            if ($str_EMLNM == "")
                $fromCompName = GetComp($ASCompanyID, $cvtconnection);
            else
                $fromCompName = $str_EMLNM;

            if ($debug)
                print("$fromCompName \n\n");

            $strFrom = "\"$fromCompName\" <$val_EMLID>";
            $strTo = str_replace(";", ",", $eMailStr);


            $strSubject = $sDesc_ARPXX;
            $strMsg = "Attached are the Mid-Week Draw adjustment files for the DATE " . date("m/d/y", strtotime($datevar_ARXXX));

            if (strlen($AdjuNotFoundStr) > 0) {
                $file_txt = "";
                if ($char_3MODE == "S") {
                    $file_txt = "DRAWINC/DRAWDEC";
                } else {
                    $file_txt = "DRAWADJ";
                }
                $strMsg .= "\n\n\n No RouteID found on these locations due to which these transactions are not included in $file_txt files.. Please add appropriate RouteID on these locations in Teak.\n\n\n" . $AdjuNotFoundStr;
            }

            $mailExObj = new htmlMimeMailEx();

            // SETTING THE "FROM" VALUES
            $mailExObj->setFrom($strFrom);

            // SETTING THE "REPLY-TO" VALUES
            $mailExObj->setReplyTo($strFrom);

            // SETTING THE "TO" VALUES
            $mailExObj->addBcc($strTo);

            // SETTING THE "SUBJECT" VALUES
            $mailExObj->setSubject($strSubject);

            // SETTING THE "MESSAGE" VALUES
            $mailExObj->setText($strMsg);

            // SET OCRRETURNNSBUY/SELL CSV ATTACHMENT
            foreach ($FileArr as $fileNameNM => $csvFileNamePT) {
                $attachment = $mailExObj->getFile($csvFileNamePT);
                $mailExObj->addAttachment($attachment, $fileNameNM);

                if ($char_FTP59 == "Y") {
                    $csvData = file_get_contents($csvFileNamePT);

                    $qryGetNNCPR = "SELECT intvar
                                    FROM system 
                                    WHERE recid = 'NNCPR' ";
                    $rsltGetNNCPR = mysqli_query($cvtconnection, $qryGetNNCPR) or die("error in query $qryGetNNCPR");
                    if ($rsltGetNNCPR) {
                        $rowGetNNCPR = mysqli_fetch_assoc($rsltGetNNCPR);
                        $commNbr = $rowGetNNCPR[intvar];
                    }
                    $nextcommNbr = $commNbr + 1;

                    $qryUpdateNNCPR = "UPDATE system
                                       SET intvar = '$nextcommNbr'
                                       WHERE recid = 'NNCPR' ";
                    $rsltUpdateNNCPR = mysqli_query($cvtconnection, $qryUpdateNNCPR) or die("error in query $qryUpdateNNCPR");
                    $strIdentification = "[ftp]" . $fileNameNM;
                    $qryInsOutGing = "INSERT INTO outgoingmsg
                                    (commnbr, reccreatets, channelid, channelsession, identification, 
                                    procflag,prioritycode,message)
                                    VALUES('$commNbr', now(), '59', 0, '" . $strIdentification . "', 
                                    'N', '3','" . addslashes($csvData) . "')";
                    if ($debug)
                        print("<b>" . $qryInsOutGing . "</b>\n\n");
                    $rsltInsOutGing = mysqli_query($cvtconnection, $qryInsOutGing) or die("error in query $qryInsOutGing");
                }//ENd if ($char_FTP59 == "Y")
            }

            if ($mailExObj->send("mail")) {
                if ($debug) {
                    print("Mail Sent to $strTo\n\n");
                }
            } else {
                if ($debug) {
                    print("Mail Not Sent - Some problem exist\n\n");
                }
            }
        }
    }//if (($str_ARSXX < $currDate) && ($char_ARS == "S" || $char_ARS == "Y"))
}//if($cvtconnection) 

function fn_create_file_logic($val_TODTI, $val_1FILR, $char_3MODE, $char_ARPXX, $datevar_ARXXX, $flag_pub, $dirPath, $FileArr, $debug, $cvtconnection) {
    $CurTime = date("His");
    $file_val = "";

    ####Draw Adjustment files####
    $extChk = '';
    $orderBy = '';
    $groupBy = '';
    if ($val_TODTI != 'A') {
        $extChk = " AND rtcode != '' ";
        $orderBy = " ORDER BY rtcode,pubcode,pubdate ";
        $groupBy = " GROUP BY rtcode,pubid,pubdate ";
    } else {
        $orderBy = " ORDER BY barcode,pubdate ";
        if ($val_1FILR == "Y") {
            $groupBy = " GROUP BY locationid,pubcode,pubdate ";
        } else {
            $groupBy = " GROUP BY locationid,pubdate ";
        }
    }

    $qryGetTempCnt = "SELECT count(*) tmpCnt FROM tmp_emr65 WHERE difftype != 'No' $extChk";
    if ($debug)
        print $qryGetTempCnt . "\n";
    $rsltGetTempCnt = mysqli_query($cvtconnection, $qryGetTempCnt)or die("Error");
    $rowGetTempCnt = mysqli_fetch_assoc($rsltGetTempCnt);
    $tmpCnt = $rowGetTempCnt[tmpCnt];

    //HTML FILE CREATION
    if ($tmpCnt != 0) {
        #####################
        //create csv file
        if ($val_1FILR == 'Y')
            $qryGetTempPub = "SELECT count(*) pubCode FROM tmp_emr65 WHERE difftype != 'No' $extChk";
        else
            $qryGetTempPub = "SELECT distinct(pubcode) pubCode FROM tmp_emr65 WHERE difftype != 'No' $extChk";
        if ($debug)
            print "$qryGetTempPub\n";
        $rsltGetTempPub = mysqli_query($cvtconnection, $qryGetTempPub)or die("Error");
        while ($rowTmpPub = mysqli_fetch_assoc($rsltGetTempPub)) {
            $prodCode = $rowTmpPub['pubCode'];

            $findPub = '';
            if ($val_1FILR == 'Y')
                $findPub = '';
            else
                $findPub = " tm.pubcode = '$prodCode'  AND ";
            if ($char_3MODE == "S") {
                $loop_ary = array("Add", "Cut");
                foreach ($loop_ary as $k) {
                    $qryGetInfo = "SELECT locationid, rtcode, SUM(diffqty) DiffQty, SUM(taqty) taQty, pubdate, barcode, pubcode FROM tmp_emr65 tm WHERE  $findPub  difftype = '$k' $extChk $groupBy $orderBy";
                    if ($debug)
                        print $qryGetInfo . "\n";
                    $rsltGetInfo = mysqli_query($GLOBALS["___mysqli_ston"], $qryGetInfo)or die("Error");
                    if (!$rsltGetInfo) {
                        $fileLineNbr = __LINE__;
                        $strErrorCode = getErrorCode(14, 01);
                        print("\n<span class=\"dataerror\">Error Occurred. ErrorCode: " . $strErrorCode . "</span>\n\n\n");
                        unset($fileLineNbr);
                    } else {
                        if (mysqli_num_rows($rsltGetInfo) > 0) {
                            $draw_val = "";
                            if ($k == "Add") {
                                $draw_val = "DRAWINC";
                            } else if ($k == "Cut") {
                                $draw_val = "DRAWDEC";
                            }
                            if ($val_1FILR == 'Y') {
                                $fileNameRP = $draw_val . date("mdy", strtotime($datevar_ARXXX)) . ".csv";
                            } else {
                                $fileNameRP = $prodCode . $draw_val . date("mdy", strtotime($datevar_ARXXX)) . ".csv";
                            }

                            $csvFileNameRP = "" . $dirPath . "/" . $fileNameRP;
                            $csvObj = new CSV($csvFileNameRP, 'W');

                            $FileArr[$fileNameRP] = $csvFileNameRP;

                            $csvArray = array();
                            unset($csvArray);

                            while ($rowGetInfo = mysqli_fetch_assoc($rsltGetInfo)) {
                                $custLocId = $rowGetInfo[locationid];
                                $prodCode = $rowGetInfo[pubcode];
                                if ($val_TODTI == 'A') {
                                    $rtcode = $rowGetInfo[barcode];
                                } else {
                                    $rtcode = $rowGetInfo[rtcode];
                                    if ($val_1FILR != 'Y') {
                                        if (stristr($rtcode, "!" . $prodCode)) {
                                            $rtcode = str_replace("!" . $prodCode, "", $rtcode);
                                        }
                                    } else {
                                        if ($rtcode != '') {
                                            if (stristr($rtcode, "!" . $prodCode)) {
                                                #$rtcode = substr($rtcode, 4);
                                                $rtcode = str_replace("!" . $prodCode, "", $rtcode);
                                            }
                                        } else {
                                            continue;
                                        }
                                    }
                                }
                                $diffqty = $rowGetInfo[DiffQty];
                                $taqty = $rowGetInfo[taQty];
                                if ($k == "Cut") {
                                    $diffqty = $diffqty * (-1);
                                    $taqty = $taqty * (-1);
                                }
                                $pubDate = $rowGetInfo[pubdate];
                                /* 1.4 Starts */
                                $pubDate = date("m/d/y", strtotime($pubDate));
                                /* 1.4 Ends */

                                $csvArray = array();
                                if ($flag_pub == 'Y') {
                                    array_push($csvArray, $prodCode);
                                }
                                array_push($csvArray, $rtcode);
                                if ($val_1FILR == 'Y' && $flag_pub == 'N') {
                                    array_push($csvArray, $prodCode);
                                }
                                array_push($csvArray, $pubDate);
                                if ($char_ARPXX == "F") {
                                    array_push($csvArray, $taqty);
                                } else {
                                    array_push($csvArray, $diffqty);
                                }
                                array_push($csvArray, $pubDate);
                                //array_push($csvArray, 0);
                                $csvObj->AddArray($csvArray);
                                unset($csvArray);
                            }//END while ($rowGetInfo = mysql_fetch_assoc($rsltGetInfo))
                        }//END if (mysql_num_rows($rsltGetInfo) > 0) {
                    }//END ELSE
                }//END foreach($loop_ary as $k)
            }//END if($char_3MODE == "S")
            else {
                if ($val_1FILR == 'Y')
                    $fileNameRP = "DRAWADJ" . date("mdy", strtotime($datevar_ARXXX)) . "_" . $CurTime . ".csv";
                else
                    $fileNameRP = "DRAWADJ" . $prodCode . date("mdy", strtotime($datevar_ARXXX)) . "_" . $CurTime . ".csv";

                $csvFileNameRP = "" . $dirPath . "/" . $fileNameRP;
                $csvObj = new CSV($csvFileNameRP, 'W');

                $FileArr[$fileNameRP] = $csvFileNameRP;

                $csvArray = array();
                unset($csvArray);

                $qryGetInfo = "SELECT locationid, rtcode, SUM(diffqty) DiffQty, SUM(taqty) taQty, pubdate, barcode, pubcode FROM tmp_emr65 tm WHERE  $findPub difftype != 'No' $extChk $groupBy $orderBy";
                if ($debug)
                    print $qryGetInfo . "\n";
                $rsltGetInfo = mysqli_query($GLOBALS["___mysqli_ston"], $qryGetInfo)or die("Error");
                if (!$rsltGetInfo) {
                    $fileLineNbr = __LINE__;
                    $strErrorCode = getErrorCode(14, 01);
                    print("\n<span class=\"dataerror\">Error Occurred. ErrorCode: " . $strErrorCode . "</span>\n\n\n");
                    unset($fileLineNbr);
                } else {
                    while ($rowGetInfo = mysqli_fetch_assoc($rsltGetInfo)) {

                        $custLocId = $rowGetInfo[locationid];
                        $prodCode = $rowGetInfo[pubcode];
                        if ($val_TODTI == 'A') {
                            $rtcode = $rowGetInfo[barcode];
                        } else {
                            $rtcode = $rowGetInfo[rtcode];
                            if ($val_1FILR != 'Y') {
                                if (stristr($rtcode, "!" . $prodCode)) {
                                    $rtcode = str_replace("!" . $prodCode, "", $rtcode);
                                } else {
                                    
                                }
                            } else {
                                if ($rtcode != '') {
                                    if (stristr($rtcode, "!" . $prodCode)) {
                                        #$rtcode = substr($rtcode, 4);
                                        $rtcode = str_replace("!" . $prodCode, "", $rtcode);
                                    }
                                } else {
                                    continue;
                                }
                            }
                            print "\nNew RtCode:$rtcode\n";
                        }

                        $diffqty = $rowGetInfo[DiffQty];
                        $taqty = $rowGetInfo[taQty];
                        $pubDate = $rowGetInfo[pubdate];
                        /* 1.4 Starts */
                        $pubDate = date("m/d/y", strtotime($pubDate));
                        /* 1.4 Ends */

                        $csvArray = array();
                        if ($flag_pub == 'Y') {
                            array_push($csvArray, $prodCode);
                        }
                        array_push($csvArray, $rtcode);
                        if ($val_1FILR == 'Y' && $flag_pub == 'N') {
                            array_push($csvArray, $prodCode);
                        }
                        array_push($csvArray, $pubDate);

                        if ($char_ARPXX == "F") {
                            array_push($csvArray, $taqty);
                        } else {
                            array_push($csvArray, $diffqty);
                        }
                        array_push($csvArray, $pubDate);
                        //array_push($csvArray, 0);
                        $csvObj->AddArray($csvArray);
                        unset($csvArray);
                    }
                }
            }
        } //While End Product 
    }//END if ($tmpCnt != 0)
    return $FileArr;
}

?>
