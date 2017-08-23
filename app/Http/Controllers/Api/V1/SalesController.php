<?php

namespace App\Http\Controllers\Api\V1;
use Illuminate\Http\Request;
use App\Http\Requests;
use Dingo\Api\Routing\Helpers;
use App\Http\Controllers\Controller;
use League\Csv\Reader;
use File;
use Mail;
use App\Sales;
use Session;
use View;
use DB;


class SalesController extends Controller{


	public function sales($id){
		$gstin_id = decrypt($id);

		$salesInvoiceData = Sales::salesInvoiceData($gstin_id);
		$getBusinessByGstin = Sales::getBusinessByGstin($gstin_id);
		if(sizeof($salesInvoiceData) > 0){
			$totalSGST = 0;
			$totalCGST = 0;
			$totalIGST = 0;
			$totalCESS = 0;
			$totalValue = 0;
			foreach ($salesInvoiceData as $key => $value) {
				$totalSGST += $value->total_sgst_amount;
				$totalCGST += $value->total_cgst_amount;
				$totalIGST += $value->total_igst_amount;
				$totalCESS += $value->total_cess_amount;
				$totalValue += $value->total_amount;
			}
			$total = array();
			$total['totalTransactions'] = sizeof($salesInvoiceData);
			$total['totalSGST'] = $totalSGST;
			$total['totalCGST'] = $totalCGST;
			$total['totalIGST'] = $totalIGST;
			$total['totalCESS'] = $totalCESS;
			$total['totalValue'] = $totalValue;

			$data = array();
			$data['total'] = $total;
			$data['salesInvoiceData'] = $salesInvoiceData;

			$returnResponse['status'] = "success";
			$returnResponse['code'] = "302";
			$returnResponse['gstin_id'] = $id;
			$returnResponse['business_id'] = $getBusinessByGstin[0]->business_id;
			$returnResponse['message'] = "All Transactions.";
			$returnResponse['data'] = $data;
		}else{
			$returnResponse['status'] = "success";
			$returnResponse['code'] = "204";
			$returnResponse['gstin_id'] = $id;
			$returnResponse['business_id'] = $getBusinessByGstin[0]->business_id;
			$returnResponse['message'] = "No Data Found.";
			$returnResponse['data'] = '';
		}
		return view('sales.sales')->with('data', $returnResponse);
	}



	public function selectSalesInvoice($id){
		$gstin_id = decrypt($id);
		return view('sales.selectSalesInvoice')->with('data', $gstin_id);
	}



	public function goodsSalesInvoice($id){
		$gstin_id = decrypt($id);

		$data = array();
		$getBusinessByGstin = Sales::getBusinessByGstin($gstin_id);
		$getSalesInvoiceCount = Sales::getSalesInvoiceCount($gstin_id);
		$getGstinInfo = Sales::getGstinInfo($gstin_id);

		if(sizeof($getSalesInvoiceCount) > 0){
			$data['invoice_no'] = "INV".($getSalesInvoiceCount[0]->count + 1);
		}else{
			$data['invoice_no'] = "INV1";
		}

		if(sizeof($getBusinessByGstin) > 0){
			$data['gstin_id'] = $gstin_id;
			$data['business_id'] = $getBusinessByGstin[0]->business_id;
		}

		if(sizeof($getGstinInfo) > 0){
			$data['state_code'] = $getGstinInfo[0]->state_code;
			$data['state_name'] = $getGstinInfo[0]->state_name;
		}
		return view('sales.goodsSalesInvoice')->with('data', $data);
	}



	public function getContact($business_id){

		$getContact = Sales::getContact($business_id);
		if(sizeof($getContact) > 0){
			$returnResponse['status'] = "success";
			$returnResponse['code'] = "302";
			$returnResponse['message'] = "Data Found.";
			$returnResponse['data'] = $getContact;
		}else{
			$returnResponse['status'] = "success";
			$returnResponse['code'] = "204";
			$returnResponse['message'] = "No Content.";
			$returnResponse['data'] = $getContact;
		}
		return $returnResponse;
	}



	public function getStates(){

		$getStates = Sales::getStates();
		if(sizeof($getStates) > 0){
			$returnResponse['status'] = "success";
			$returnResponse['code'] = "302";
			$returnResponse['message'] = "Data Found.";
			$returnResponse['data'] = $getStates;
		}else{
			$returnResponse['status'] = "success";
			$returnResponse['code'] = "204";
			$returnResponse['message'] = "No Content.";
			$returnResponse['data'] = $getStates;
		}
		return $returnResponse;
	}



	public function getContactInfo($contact_id){

		$getContactInfo = Sales::getContactInfo($contact_id);
		if(sizeof($getContactInfo) > 0){
			$returnResponse['status'] = "success";
			$returnResponse['code'] = "302";
			$returnResponse['message'] = "Data Found.";
			$returnResponse['data'] = $getContactInfo;
		}else{
			$returnResponse['status'] = "success";
			$returnResponse['code'] = "204";
			$returnResponse['message'] = "No Content.";
			$returnResponse['data'] = $getContactInfo;
		}
		return $returnResponse;
	}



	public function getItem($business_id){

		$getItem = Sales::getItem($business_id);
		if(sizeof($getItem) > 0){
			$returnResponse['status'] = "success";
			$returnResponse['code'] = "302";
			$returnResponse['message'] = "Data Found.";
			$returnResponse['data'] = $getItem;
		}else{
			$returnResponse['status'] = "success";
			$returnResponse['code'] = "204";
			$returnResponse['message'] = "No Content.";
			$returnResponse['data'] = $getItem;
		}
		return $returnResponse;
	}



	public function getItemInfo($item_id){

		$getItemInfo = Sales::getItemInfo($item_id);
		if(sizeof($getItemInfo) > 0){
			$returnResponse['status'] = "success";
			$returnResponse['code'] = "302";
			$returnResponse['message'] = "Data Found.";
			$returnResponse['data'] = $getItemInfo;
		}else{
			$returnResponse['status'] = "success";
			$returnResponse['code'] = "204";
			$returnResponse['message'] = "No Content.";
			$returnResponse['data'] = $getItemInfo;
		}
		return $returnResponse;
	}



	public function saveSalesInvoice(Request $request){
		$input = $request->all();

		$checkInvoiceNumber = Sales::checkInvoiceNumber($input['gstin_id'],$input['invoice_no']);

		if(sizeof($checkInvoiceNumber) > 0){
			$returnResponse['status'] = "failed";
			$returnResponse['code'] = "400";
			$returnResponse['message'] = "Duplicate invoice number. Please change invoice number.";
			$returnResponse['data'] = $checkInvoiceNumber;
			return $returnResponse;
		}else{
			$salesInvoiceData = array();
			$salesInvoiceData['gstin_id'] = $input['gstin_id'];
			$salesInvoiceData['invoice_no'] = $input['invoice_no'];
			$salesInvoiceData['invoice_date'] = $input['invoice_date'];
			$salesInvoiceData['reference'] = $input['reference'];
			$salesInvoiceData['contact_gstin'] = $input['contact_gstin'];
			$salesInvoiceData['place_of_supply'] = $input['place_of_supply'];
			$salesInvoiceData['due_date'] = $input['due_date'];
			$salesInvoiceData['contact_name'] = $input['contact_name'];
			$salesInvoiceData['bill_address'] = $input['bill_address'];
			$salesInvoiceData['bill_pincode'] = $input['bill_pincode'];
			$salesInvoiceData['bill_city'] = $input['bill_city'];
			$salesInvoiceData['bill_state'] = $input['bill_state'];
			$salesInvoiceData['bill_country'] = $input['bill_country'];
			$salesInvoiceData['sh_address'] = $input['sh_address'];
			$salesInvoiceData['sh_pincode'] = $input['sh_pincode'];
			$salesInvoiceData['sh_city'] = $input['sh_city'];
			$salesInvoiceData['sh_state'] = $input['sh_state'];
			$salesInvoiceData['sh_country'] = $input['sh_country'];
			$salesInvoiceData['total_discount'] = isset($input['total_discount']) ? $input['total_discount'] : "0";
			$salesInvoiceData['total_cgst_amount'] = isset($input['total_cgst_amount']) ? $input['total_cgst_amount'] : "0";
			$salesInvoiceData['total_sgst_amount'] = isset($input['total_sgst_amount']) ? $input['total_sgst_amount'] : "0";
			$salesInvoiceData['total_igst_amount'] = isset($input['total_igst_amount']) ? $input['total_igst_amount'] : "0";
			$salesInvoiceData['total_cess_amount'] = isset($input['total_cess_amount']) ? $input['total_cess_amount'] : "0";
			$salesInvoiceData['total_amount'] = $input['total_amount'];
			$salesInvoiceData['tt_taxable_value'] = isset($input['tt_taxable_value']) ? $input['tt_taxable_value'] : "0";
			$salesInvoiceData['tt_cgst_amount'] = isset($input['tt_cgst_amount']) ? $input['tt_cgst_amount'] : "0";
			$salesInvoiceData['tt_sgst_amount'] = isset($input['tt_sgst_amount']) ? $input['tt_sgst_amount'] : "0";
			$salesInvoiceData['tt_igst_amount'] = isset($input['tt_igst_amount']) ? $input['tt_igst_amount'] : "0";
			$salesInvoiceData['tt_cess_amount'] = isset($input['tt_cess_amount']) ? $input['tt_cess_amount'] : "0";
			$salesInvoiceData['tt_total'] = isset($input['tt_total']) ? $input['tt_total'] : "0";
			$salesInvoiceData['total_in_words'] = $input['total_in_words'];
			$salesInvoiceData['total_tax'] = $input['total_tax'];
			$salesInvoiceData['grand_total'] = $input['grand_total'];

			$insertSalesInvoice = Sales::insertSalesInvoice($salesInvoiceData);
			if($insertSalesInvoice > 0){

				$getSIC = Sales::getSIC($input['gstin_id']);
				if(sizeof($getSIC) > 0){
					$count_data = array();
					$count_data['gstin_id'] = $input['gstin_id'];
					$count_data['invoice_type'] = 1;
					$count_data['count'] = $getSIC[0]->count + 1;
					$updateIC = Sales::updateIC($count_data);
				}else{
					$add_count_data = array();
					$add_count_data['gstin_id'] = $input['gstin_id'];
					$count_data['invoice_type'] = 1;
					$add_count_data['count'] = '1';
					$addIC = Sales::addIC($add_count_data);
				}

				$invoiceDetailData = array();

				if(is_array($input['total'])){
					foreach ($input['total'] as $key => $value) {
						$invoiceDetailData['invoice_no'] = $input['invoice_no'];
						$invoiceDetailData['invoice_type'] = '1';
						$invoiceDetailData['item_name'] = $input['item_name'][$key];
						$invoiceDetailData['item_value'] = $input['item_value'][$key];
						$invoiceDetailData['item_type'] = "Goods";
						$invoiceDetailData['hsn_sac_no'] = $input['hsn_sac_no'][$key];
						$invoiceDetailData['quantity'] = $input['quantity'][$key];
						$invoiceDetailData['rate'] = $input['rate'][$key];
						$invoiceDetailData['discount'] = $input['discount'][$key];
						$invoiceDetailData['cgst_percentage'] = isset($input['cgst_percentage'][$key]) ? $input['cgst_percentage'][$key] : "0";
						$invoiceDetailData['cgst_amount'] = isset($input['cgst_amount'][$key]) ? $input['cgst_amount'][$key] : "0";
						$invoiceDetailData['sgst_percentage'] = isset($input['sgst_percentage'][$key]) ? $input['sgst_percentage'][$key] : "0";
						$invoiceDetailData['sgst_amount'] = isset($input['sgst_amount'][$key]) ? $input['sgst_amount'][$key] : "0";
						$invoiceDetailData['igst_percentage'] = isset($input['igst_percentage'][$key]) ? $input['igst_percentage'][$key] : "0";
						$invoiceDetailData['igst_amount'] = isset($input['igst_amount'][$key]) ? $input['igst_amount'][$key] : "0";
						$invoiceDetailData['cess_percentage'] = isset($input['cess_percentage'][$key]) ? $input['cess_percentage'][$key] : "0";
						$invoiceDetailData['cess_amount'] = isset($input['cess_amount'][$key]) ? $input['cess_amount'][$key] : "0";
						$invoiceDetailData['total'] = $input['total'][$key];
						$insertInvoiceDetails = Sales::insertInvoiceDetails($invoiceDetailData);
					}
					$returnResponse['status'] = "success";
					$returnResponse['code'] = "201";
					$returnResponse['message'] = "Invoice created successfully.";
					$returnResponse['data'] = $insertSalesInvoice;
					return $returnResponse;
				}else{
					$invoiceDetailData['invoice_no'] = $input['invoice_no'];
					$invoiceDetailData['invoice_type'] = '1';
					$invoiceDetailData['item_name'] = $input['item_name'];
					$invoiceDetailData['item_type'] = "Goods";
					$invoiceDetailData['hsn_sac_no'] = $input['hsn_sac_no'];
					$invoiceDetailData['quantity'] = $input['quantity'];
					$invoiceDetailData['rate'] = $input['rate'];
					$invoiceDetailData['discount'] = $input['discount'];
					$invoiceDetailData['cgst_percentage'] = isset($input['cgst_percentage']) ? $input['cgst_percentage'] : "0";
					$invoiceDetailData['cgst_amount'] = isset($input['cgst_amount']) ? $input['cgst_amount'] : "0";
					$invoiceDetailData['sgst_percentage'] = isset($input['sgst_percentage']) ? $input['sgst_percentage'] : "0";
					$invoiceDetailData['sgst_amount'] = isset($input['sgst_amount']) ? $input['sgst_amount'] : "0";
					$invoiceDetailData['igst_percentage'] = isset($input['igst_percentage']) ? $input['igst_percentage'] : "0";
					$invoiceDetailData['igst_amount'] = isset($input['igst_amount']) ? $input['igst_amount'] : "0";
					$invoiceDetailData['cess_percentage'] = isset($input['cess_percentage']) ? $input['cess_percentage'] : "0";
					$invoiceDetailData['cess_amount'] = isset($input['cess_amount']) ? $input['cess_amount'] : "0";
					$invoiceDetailData['total'] = $input['total'];
					$insertInvoiceDetails = Sales::insertInvoiceDetails($invoiceDetailData);

					$returnResponse['status'] = "success";
					$returnResponse['code'] = "201";
					$returnResponse['message'] = "Invoice created successfully.";
					$returnResponse['data'] = $insertSalesInvoice;
					return $returnResponse;
				}
			}else{
				$returnResponse['status'] = "failed";
				$returnResponse['code'] = "400";
				$returnResponse['message'] = "Error while creating invoice. Please try again.";
				$returnResponse['data'] = $insertSalesInvoice;
				return $returnResponse;
			}
		}
		return $returnResponse;
	}



	public function cancelInvoice($id){
		$getData = Sales::cancelInvoice($id);

		if (sizeof($getData) > 0) {
			$returnResponse['status'] = "success";
			$returnResponse['code'] = "200";
			$returnResponse['message'] = "Invoice cncelled successfully.";
			$returnResponse['data'] = $getData;
		}else{
			$returnResponse['status'] = "success";
			$returnResponse['code'] = "204";
			$returnResponse['message'] = "Something went wrong while cancelling invoice.";
			$returnResponse['data'] = $getData;
		}
		return response()->json($returnResponse);
	}



	public function editSalesInvoice($id){
		$si_id = decrypt($id);
		$getData = Sales::getSalesInvoiceData($si_id);

		if (sizeof($getData) > 0) {
			$getInvoiceDetail = Sales::getInvoiceDetail($getData[0]->invoice_no);
			$getBusinessByGstin = Sales::getBusinessByGstin($getData[0]->gstin_id);
			$getGstinInfo = Sales::getGstinInfo($getData[0]->gstin_id);
			if(sizeof($getGstinInfo) > 0){
				$returnResponse['state_code'] = $getGstinInfo[0]->state_code;
				$returnResponse['state_name'] = $getGstinInfo[0]->state_name;
			}

			$data = array();
			$data['invoice_data'] = $getData;
			$data['invoice_details'] = $getInvoiceDetail;

			if(sizeof($getBusinessByGstin) > 0){
				$returnResponse['business_id'] = $getBusinessByGstin[0]->business_id;
			}

			$returnResponse['status'] = "success";
			$returnResponse['code'] = "200";
			$returnResponse['message'] = "Data found.";
			$returnResponse['data'] = $data;
		}else{
			$returnResponse['status'] = "success";
			$returnResponse['code'] = "204";
			$returnResponse['message'] = "No data found.";
			$returnResponse['data'] = $getData;
		}
		return view('sales.editSalesInvoice')->with('data', $returnResponse);
	}



	public function deleteInvoiceDetail($id){
		$getData = Sales::deleteInvoiceDetail($id);

		if (sizeof($getData) > 0) {
			$returnResponse['status'] = "success";
			$returnResponse['code'] = "200";
			$returnResponse['message'] = "Invoice detail deleted successfully.";
			$returnResponse['data'] = $getData;
		}else{
			$returnResponse['status'] = "success";
			$returnResponse['code'] = "204";
			$returnResponse['message'] = "Something went wrong while deleting invoice details.";
			$returnResponse['data'] = $getData;
		}
		return response()->json($returnResponse);
	}



	public function updateSalesInvoice(Request $request,$si_id){
		$input = $request->all();

		$salesInvoiceData = array();
		$salesInvoiceData['gstin_id'] = $input['gstin_id'];
		$salesInvoiceData['invoice_no'] = $input['invoice_no'];
		$salesInvoiceData['invoice_date'] = $input['invoice_date'];
		$salesInvoiceData['reference'] = $input['reference'];
		$salesInvoiceData['contact_gstin'] = $input['contact_gstin'];
		$salesInvoiceData['place_of_supply'] = $input['place_of_supply'];
		$salesInvoiceData['due_date'] = $input['due_date'];
		$salesInvoiceData['contact_name'] = $input['contact_name'];
		$salesInvoiceData['bill_address'] = $input['bill_address'];
		$salesInvoiceData['bill_pincode'] = $input['bill_pincode'];
		$salesInvoiceData['bill_city'] = $input['bill_city'];
		$salesInvoiceData['bill_state'] = $input['bill_state'];
		$salesInvoiceData['bill_country'] = $input['bill_country'];
		$salesInvoiceData['sh_address'] = $input['sh_address'];
		$salesInvoiceData['sh_pincode'] = $input['sh_pincode'];
		$salesInvoiceData['sh_city'] = $input['sh_city'];
		$salesInvoiceData['sh_state'] = $input['sh_state'];
		$salesInvoiceData['sh_country'] = $input['sh_country'];
		$salesInvoiceData['total_discount'] = isset($input['total_discount']) ? $input['total_discount'] : "0";
		$salesInvoiceData['total_cgst_amount'] = isset($input['total_cgst_amount']) ? $input['total_cgst_amount'] : "0";
		$salesInvoiceData['total_sgst_amount'] = isset($input['total_sgst_amount']) ? $input['total_sgst_amount'] : "0";
		$salesInvoiceData['total_igst_amount'] = isset($input['total_igst_amount']) ? $input['total_igst_amount'] : "0";
		$salesInvoiceData['total_cess_amount'] = isset($input['total_cess_amount']) ? $input['total_cess_amount'] : "0";
		$salesInvoiceData['total_amount'] = $input['total_amount'];
		$salesInvoiceData['tt_taxable_value'] = isset($input['tt_taxable_value']) ? $input['tt_taxable_value'] : "0";
		$salesInvoiceData['tt_cgst_amount'] = isset($input['tt_cgst_amount']) ? $input['tt_cgst_amount'] : "0";
		$salesInvoiceData['tt_sgst_amount'] = isset($input['tt_sgst_amount']) ? $input['tt_sgst_amount'] : "0";
		$salesInvoiceData['tt_igst_amount'] = isset($input['tt_igst_amount']) ? $input['tt_igst_amount'] : "0";
		$salesInvoiceData['tt_cess_amount'] = isset($input['tt_cess_amount']) ? $input['tt_cess_amount'] : "0";
		$salesInvoiceData['tt_total'] = isset($input['tt_total']) ? $input['tt_total'] : "0";
		$salesInvoiceData['total_in_words'] = $input['total_in_words'];
		$salesInvoiceData['total_tax'] = $input['total_tax'];
		$salesInvoiceData['grand_total'] = $input['grand_total'];
		
		$insertSalesInvoice = Sales::updateSalesInvoice($salesInvoiceData,$si_id);
		if($insertSalesInvoice > 0){

			$invoiceDetailData = array();
			$deleteInvoiceDetailBySiId = Sales::deleteInvoiceDetailBySiId($input['invoice_no']);

			if(is_array($input['total'])){
				foreach ($input['total'] as $key => $value) {
					$invoiceDetailData['invoice_no'] = $input['invoice_no'];
					$invoiceDetailData['invoice_type'] = '1';
					$invoiceDetailData['item_name'] = $input['item_name'][$key];
					$invoiceDetailData['item_value'] = $input['item_value'][$key];
					$invoiceDetailData['item_type'] = "Goods";
					$invoiceDetailData['hsn_sac_no'] = $input['hsn_sac_no'][$key];
					$invoiceDetailData['quantity'] = $input['quantity'][$key];
					$invoiceDetailData['rate'] = $input['rate'][$key];
					$invoiceDetailData['discount'] = $input['discount'][$key];
					$invoiceDetailData['cgst_percentage'] = isset($input['cgst_percentage'][$key]) ? $input['cgst_percentage'][$key] : "0";
					$invoiceDetailData['cgst_amount'] = isset($input['cgst_amount'][$key]) ? $input['cgst_amount'][$key] : "0";
					$invoiceDetailData['sgst_percentage'] = isset($input['sgst_percentage'][$key]) ? $input['sgst_percentage'][$key] : "0";
					$invoiceDetailData['sgst_amount'] = isset($input['sgst_amount'][$key]) ? $input['sgst_amount'][$key] : "0";
					$invoiceDetailData['igst_percentage'] = isset($input['igst_percentage'][$key]) ? $input['igst_percentage'][$key] : "0";
					$invoiceDetailData['igst_amount'] = isset($input['igst_amount'][$key]) ? $input['igst_amount'][$key] : "0";
					$invoiceDetailData['cess_percentage'] = isset($input['cess_percentage'][$key]) ? $input['cess_percentage'][$key] : "0";
					$invoiceDetailData['cess_amount'] = isset($input['cess_amount'][$key]) ? $input['cess_amount'][$key] : "0";
					$invoiceDetailData['total'] = $input['total'][$key];
					$insertInvoiceDetails = Sales::insertInvoiceDetails($invoiceDetailData);
				}
				$returnResponse['status'] = "success";
				$returnResponse['code'] = "201";
				$returnResponse['message'] = "Invoice updated successfully.";
				$returnResponse['data'] = $insertSalesInvoice;
				return $returnResponse;
			}else{
				$invoiceDetailData['invoice_no'] = $input['invoice_no'];
				$invoiceDetailData['invoice_type'] = '1';
				$invoiceDetailData['item_name'] = $input['item_name'];
				$invoiceDetailData['item_type'] = "Goods";
				$invoiceDetailData['hsn_sac_no'] = $input['hsn_sac_no'];
				$invoiceDetailData['quantity'] = $input['quantity'];
				$invoiceDetailData['rate'] = $input['rate'];
				$invoiceDetailData['discount'] = $input['discount'];
				$invoiceDetailData['cgst_percentage'] = isset($input['cgst_percentage']) ? $input['cgst_percentage'] : "0";
				$invoiceDetailData['cgst_amount'] = isset($input['cgst_amount']) ? $input['cgst_amount'] : "0";
				$invoiceDetailData['sgst_percentage'] = isset($input['sgst_percentage']) ? $input['sgst_percentage'] : "0";
				$invoiceDetailData['sgst_amount'] = isset($input['sgst_amount']) ? $input['sgst_amount'] : "0";
				$invoiceDetailData['igst_percentage'] = isset($input['igst_percentage']) ? $input['igst_percentage'] : "0";
				$invoiceDetailData['igst_amount'] = isset($input['igst_amount']) ? $input['igst_amount'] : "0";
				$invoiceDetailData['cess_percentage'] = isset($input['cess_percentage']) ? $input['cess_percentage'] : "0";
				$invoiceDetailData['cess_amount'] = isset($input['cess_amount']) ? $input['cess_amount'] : "0";
				$invoiceDetailData['total'] = $input['total'];
				$insertInvoiceDetails = Sales::insertInvoiceDetails($invoiceDetailData);

				$returnResponse['status'] = "success";
				$returnResponse['code'] = "201";
				$returnResponse['message'] = "Invoice updated successfully.";
				$returnResponse['data'] = $insertSalesInvoice;
				return $returnResponse;
			}
		}else{
			$returnResponse['status'] = "failed";
			$returnResponse['code'] = "400";
			$returnResponse['message'] = "Error while updating invoice. Please try again.";
			$returnResponse['data'] = $insertSalesInvoice;
			return $returnResponse;
		}
		return $returnResponse;
	}



	public function cdnote($id){
		$gstin_id = decrypt($id);
		$creditDebitNoteData = Sales::creditDebitNoteData($gstin_id);
		$getBusinessByGstin = Sales::getBusinessByGstin($gstin_id);
		if(sizeof($creditDebitNoteData) > 0){
			$creditTransaction = 0;
			$creditValue = 0;
			$debitTransaction = 0;
			$debitValue = 0;
			foreach ($creditDebitNoteData as $key => $value) {
				if($value->note_type == 1){
					$key = 1;
					$creditTransaction += $key;
					$creditValue += $value->total_amount;
				}
				if($value->note_type == 2){
					$debitTransaction += $key;
					$debitValue += $value->total_amount;
				}
			}
			$total = array();
			$total['totalTransactions'] = sizeof($creditDebitNoteData);
			$total['creditTransaction'] = $creditTransaction;
			$total['creditValue'] = $creditValue;
			$total['debitTransaction'] = $debitTransaction;
			$total['debitValue'] = $debitValue;

			$data = array();
			$data['total'] = $total;
			$data['creditDebitNoteData'] = $creditDebitNoteData;

			$returnResponse['status'] = "success";
			$returnResponse['code'] = "302";
			$returnResponse['gstin_id'] = $id;
			$returnResponse['business_id'] = $getBusinessByGstin[0]->business_id;
			$returnResponse['message'] = "All Transactions.";
			$returnResponse['data'] = $data;
		}else{
			$returnResponse['status'] = "success";
			$returnResponse['code'] = "204";
			$returnResponse['gstin_id'] = $id;
			$returnResponse['business_id'] = $getBusinessByGstin[0]->business_id;
			$returnResponse['message'] = "No Data Found.";
			$returnResponse['data'] = '';
		}
		return view('sales.cdnote')->with('data', $returnResponse);
	}



	public function createCdnote($id){
		$gstin_id = decrypt($id);

		$data = array();
		$getBusinessByGstin = Sales::getBusinessByGstin($gstin_id);
		$getCdnoteInvoiceCount = Sales::getCdnoteInvoiceCount($gstin_id);
		$getGstinInfo = Sales::getGstinInfo($gstin_id);

		if(sizeof($getCdnoteInvoiceCount) > 0){
			$data['note_no'] = "CDN".($getCdnoteInvoiceCount[0]->count + 1);
		}else{
			$data['note_no'] = "CDN1";
		}

		if(sizeof($getBusinessByGstin) > 0){
			$data['gstin_id'] = $gstin_id;
			$data['business_id'] = $getBusinessByGstin[0]->business_id;
		}

		if(sizeof($getGstinInfo) > 0){
			$data['state_code'] = $getGstinInfo[0]->state_code;
			$data['state_name'] = $getGstinInfo[0]->state_name;
		}
		return view('sales.createCdnote')->with('data', $data);
	}



	public function getInvoice($gstin){

		$getInvoice = Sales::getInvoice($gstin);
		if(sizeof($getInvoice) > 0){
			$returnResponse['status'] = "success";
			$returnResponse['code'] = "302";
			$returnResponse['message'] = "Data Found.";
			$returnResponse['data'] = $getInvoice;
		}else{
			$returnResponse['status'] = "success";
			$returnResponse['code'] = "204";
			$returnResponse['message'] = "No Content.";
			$returnResponse['data'] = $getInvoice;
		}
		return $returnResponse;
	}



	public function getInvoiceInfo($si_id){

		$getInvoiceInfo = Sales::getInvoiceInfo($si_id);
		if(sizeof($getInvoiceInfo) > 0){
			$returnResponse['status'] = "success";
			$returnResponse['code'] = "302";
			$returnResponse['message'] = "Data Found.";
			$returnResponse['data'] = $getInvoiceInfo;
		}else{
			$returnResponse['status'] = "success";
			$returnResponse['code'] = "204";
			$returnResponse['message'] = "No Content.";
			$returnResponse['data'] = $getInvoiceInfo;
		}
		return $returnResponse;
	}



	public function saveCdnote(Request $request){
		$input = $request->all();

		$cdnoteData = array();
		$cdnoteData['gstin_id'] = $input['gstin_id'];
		$cdnoteData['note_type'] = $input['note_type'];
		$cdnoteData['note_no'] = $input['note_no'];
		$cdnoteData['invoice_no'] = $input['invoice_no'];
		$cdnoteData['contact_name'] = $input['contact_name'];
		$cdnoteData['note_issue_date'] = $input['note_issue_date'];
		$cdnoteData['contact_gstin'] = $input['contact_gstin'];
		$cdnoteData['place_of_supply'] = $input['place_of_supply'];
		$cdnoteData['bill_address'] = $input['bill_address'];
		$cdnoteData['bill_pincode'] = $input['bill_pincode'];
		$cdnoteData['bill_city'] = $input['bill_city'];
		$cdnoteData['bill_state'] = $input['bill_state'];
		$cdnoteData['bill_country'] = $input['bill_country'];
		$cdnoteData['sh_address'] = $input['sh_address'];
		$cdnoteData['sh_pincode'] = $input['sh_pincode'];
		$cdnoteData['sh_city'] = $input['sh_city'];
		$cdnoteData['sh_state'] = $input['sh_state'];
		$cdnoteData['sh_country'] = $input['sh_country'];
		$cdnoteData['total_discount'] = isset($input['total_discount']) ? $input['total_discount'] : "0";
		$cdnoteData['total_cgst_amount'] = isset($input['total_cgst_amount']) ? $input['total_cgst_amount'] : "0";
		$cdnoteData['total_sgst_amount'] = isset($input['total_sgst_amount']) ? $input['total_sgst_amount'] : "0";
		$cdnoteData['total_igst_amount'] = isset($input['total_igst_amount']) ? $input['total_igst_amount'] : "0";
		$cdnoteData['total_cess_amount'] = isset($input['total_cess_amount']) ? $input['total_cess_amount'] : "0";
		$cdnoteData['total_amount'] = $input['total_amount'];
		$cdnoteData['grand_total'] = $input['grand_total'];
		$cdnoteData['total_in_words'] = $input['total_in_words'];
		$cdnoteData['total_tax'] = $input['total_tax'];
		//return $cdnoteData;
		$insertCdnote = Sales::insertCdnote($cdnoteData);
		if($insertCdnote > 0){

			$getCDNC = Sales::getCDNC($input['gstin_id']);
			if(sizeof($getCDNC) > 0){
				$count_data = array();
				$count_data['gstin_id'] = $input['gstin_id'];
				$count_data['invoice_type'] = 2;
				$count_data['count'] = $getCDNC[0]->count + 1;
				$updateIC = Sales::updateCDNC($count_data);
			}else{
				$add_count_data = array();
				$add_count_data['gstin_id'] = $input['gstin_id'];
				$count_data['invoice_type'] = 2;
				$add_count_data['count'] = '1';
				$addIC = Sales::addCDNC($add_count_data);
			}

			$cdnoteDetailData = array();

			if(is_array($input['total'])){
				foreach ($input['total'] as $key => $value) {
					$cdnoteDetailData['invoice_no'] = $input['note_no'];
					$cdnoteDetailData['invoice_type'] = '2';
					$cdnoteDetailData['item_name'] = $input['item_name'][$key];
					$cdnoteDetailData['item_value'] = $input['item_value'][$key];
					$cdnoteDetailData['item_type'] = "Goods";
					$cdnoteDetailData['hsn_sac_no'] = $input['hsn_sac_no'][$key];
					$cdnoteDetailData['quantity'] = $input['quantity'][$key];
					$cdnoteDetailData['rate'] = $input['rate'][$key];
					$cdnoteDetailData['discount'] = $input['discount'][$key];
					$cdnoteDetailData['cgst_percentage'] = isset($input['cgst_percentage'][$key]) ? $input['cgst_percentage'][$key] : "0";
					$cdnoteDetailData['cgst_amount'] = isset($input['cgst_amount'][$key]) ? $input['cgst_amount'][$key] : "0";
					$cdnoteDetailData['sgst_percentage'] = isset($input['sgst_percentage'][$key]) ? $input['sgst_percentage'][$key] : "0";
					$cdnoteDetailData['sgst_amount'] = isset($input['sgst_amount'][$key]) ? $input['sgst_amount'][$key] : "0";
					$cdnoteDetailData['igst_percentage'] = isset($input['igst_percentage'][$key]) ? $input['igst_percentage'][$key] : "0";
					$cdnoteDetailData['igst_amount'] = isset($input['igst_amount'][$key]) ? $input['igst_amount'][$key] : "0";
					$cdnoteDetailData['cess_percentage'] = isset($input['cess_percentage'][$key]) ? $input['cess_percentage'][$key] : "0";
					$cdnoteDetailData['cess_amount'] = isset($input['cess_amount'][$key]) ? $input['cess_amount'][$key] : "0";
					$cdnoteDetailData['total'] = $input['total'][$key];
					$insertInvoiceDetails = Sales::insertInvoiceDetails($cdnoteDetailData);
				}
				$returnResponse['status'] = "success";
				$returnResponse['code'] = "201";
				$returnResponse['message'] = "Note created successfully.";
				$returnResponse['data'] = $insertCdnote;
				return $returnResponse;
			}else{
				$cdnoteDetailData['invoice_no'] = $input['note_no'];
				$cdnoteDetailData['invoice_type'] = '1';
				$cdnoteDetailData['item_name'] = $input['item_name'];
				$cdnoteDetailData['item_type'] = "Goods";
				$cdnoteDetailData['hsn_sac_no'] = $input['hsn_sac_no'];
				$cdnoteDetailData['quantity'] = $input['quantity'];
				$cdnoteDetailData['rate'] = $input['rate'];
				$cdnoteDetailData['discount'] = $input['discount'];
				$cdnoteDetailData['cgst_percentage'] = isset($input['cgst_percentage']) ? $input['cgst_percentage'] : "0";
				$cdnoteDetailData['cgst_amount'] = isset($input['cgst_amount']) ? $input['cgst_amount'] : "0";
				$cdnoteDetailData['sgst_percentage'] = isset($input['sgst_percentage']) ? $input['sgst_percentage'] : "0";
				$cdnoteDetailData['sgst_amount'] = isset($input['sgst_amount']) ? $input['sgst_amount'] : "0";
				$cdnoteDetailData['igst_percentage'] = isset($input['igst_percentage']) ? $input['igst_percentage'] : "0";
				$cdnoteDetailData['igst_amount'] = isset($input['igst_amount']) ? $input['igst_amount'] : "0";
				$cdnoteDetailData['cess_percentage'] = isset($input['cess_percentage']) ? $input['cess_percentage'] : "0";
				$cdnoteDetailData['cess_amount'] = isset($input['cess_amount']) ? $input['cess_amount'] : "0";
				$cdnoteDetailData['total'] = $input['total'];
				$insertInvoiceDetails = Sales::insertInvoiceDetails($cdnoteDetailData);

				$returnResponse['status'] = "success";
				$returnResponse['code'] = "201";
				$returnResponse['message'] = "Note created successfully.";
				$returnResponse['data'] = $insertCdnote;
				return $returnResponse;
			}
		}else{
			$returnResponse['status'] = "failed";
			$returnResponse['code'] = "400";
			$returnResponse['message'] = "Error while creating invoice. Please try again.";
			$returnResponse['data'] = $insertCdnote;
			return $returnResponse;
		}
		return $returnResponse;
	}



	public function cancelCdnote($id){
		$getData = Sales::cancelCdnote($id);

		if (sizeof($getData) > 0) {
			$returnResponse['status'] = "success";
			$returnResponse['code'] = "200";
			$returnResponse['message'] = "Note cncelled successfully.";
			$returnResponse['data'] = $getData;
		}else{
			$returnResponse['status'] = "success";
			$returnResponse['code'] = "204";
			$returnResponse['message'] = "Something went wrong while cancelling note.";
			$returnResponse['data'] = $getData;
		}
		return response()->json($returnResponse);
	}



	public function editCdnote($id){
		$cdn_id = decrypt($id);
		$getData = Sales::getCdnoteData($cdn_id);

		if (sizeof($getData) > 0) {
			$getInvoiceDetail = Sales::getInvoiceDetail($getData[0]->invoice_no);
			$getBusinessByGstin = Sales::getBusinessByGstin($getData[0]->gstin_id);
			$getGstinInfo = Sales::getGstinInfo($getData[0]->gstin_id);
			if(sizeof($getGstinInfo) > 0){
				$returnResponse['state_code'] = $getGstinInfo[0]->state_code;
				$returnResponse['state_name'] = $getGstinInfo[0]->state_name;
			}

			$data = array();
			$data['invoice_data'] = $getData;
			$data['invoice_details'] = $getInvoiceDetail;

			if(sizeof($getBusinessByGstin) > 0){
				$returnResponse['business_id'] = $getBusinessByGstin[0]->business_id;
			}

			$returnResponse['status'] = "success";
			$returnResponse['code'] = "200";
			$returnResponse['message'] = "Data found.";
			$returnResponse['data'] = $data;
		}else{
			$returnResponse['status'] = "success";
			$returnResponse['code'] = "204";
			$returnResponse['message'] = "No data found.";
			$returnResponse['data'] = $getData;
		}
		return view('sales.editCdnote')->with('data', $returnResponse);
	}



	public function createAdvanceReceipt($id){
		$gstin_id = decrypt($id);

		$data = array();
		$getBusinessByGstin = Sales::getBusinessByGstin($gstin_id);
		$getAdvanceReceiptCount = Sales::getAdvanceReceiptCount($gstin_id);
		$getGstinInfo = Sales::getGstinInfo($gstin_id);

		if(sizeof($getAdvanceReceiptCount) > 0){
			$data['receipt_no'] = "ADN".($getAdvanceReceiptCount[0]->count + 1);
		}else{
			$data['receipt_no'] = "ADN1";
		}

		if(sizeof($getBusinessByGstin) > 0){
			$data['gstin_id'] = $gstin_id;
			$data['business_id'] = $getBusinessByGstin[0]->business_id;
		}

		if(sizeof($getGstinInfo) > 0){
			$data['state_code'] = $getGstinInfo[0]->state_code;
			$data['state_name'] = $getGstinInfo[0]->state_name;
		}
		return view('sales.createAdvanceReceipt')->with('data', $data);
	}



	public function saveAdvanceReceipt(Request $request){
		$input = $request->all();

		$advanceReceiptData = array();
		$advanceReceiptData['gstin_id'] = $input['gstin_id'];
		$advanceReceiptData['receipt_no'] = $input['receipt_no'];
		$advanceReceiptData['receipt_date'] = $input['receipt_date'];
		$advanceReceiptData['contact_gstin'] = $input['contact_gstin'];
		$advanceReceiptData['place_of_supply'] = $input['place_of_supply'];
		$advanceReceiptData['contact_name'] = $input['contact_name'];
		$advanceReceiptData['bill_address'] = $input['bill_address'];
		$advanceReceiptData['bill_pincode'] = $input['bill_pincode'];
		$advanceReceiptData['bill_city'] = $input['bill_city'];
		$advanceReceiptData['bill_state'] = $input['bill_state'];
		$advanceReceiptData['bill_country'] = $input['bill_country'];
		$advanceReceiptData['sh_address'] = $input['sh_address'];
		$advanceReceiptData['sh_pincode'] = $input['sh_pincode'];
		$advanceReceiptData['sh_city'] = $input['sh_city'];
		$advanceReceiptData['sh_state'] = $input['sh_state'];
		$advanceReceiptData['sh_country'] = $input['sh_country'];
		$advanceReceiptData['total_discount'] = isset($input['total_discount']) ? $input['total_discount'] : "0";
		$advanceReceiptData['total_cgst_amount'] = isset($input['total_cgst_amount']) ? $input['total_cgst_amount'] : "0";
		$advanceReceiptData['total_sgst_amount'] = isset($input['total_sgst_amount']) ? $input['total_sgst_amount'] : "0";
		$advanceReceiptData['total_igst_amount'] = isset($input['total_igst_amount']) ? $input['total_igst_amount'] : "0";
		$advanceReceiptData['total_cess_amount'] = isset($input['total_cess_amount']) ? $input['total_cess_amount'] : "0";
		$advanceReceiptData['total_amount'] = $input['total_amount'];
		$advanceReceiptData['total_in_words'] = $input['total_in_words'];
		$advanceReceiptData['total_tax'] = $input['total_tax'];
		$advanceReceiptData['grand_total'] = $input['grand_total'];
		
		$insertAdvanceReceipt = Sales::insertAdvanceReceipt($advanceReceiptData);
		if($insertAdvanceReceipt > 0){

			$getARC = Sales::getARC($input['gstin_id']);
			if(sizeof($getARC) > 0){
				$count_data = array();
				$count_data['gstin_id'] = $input['gstin_id'];
				$count_data['invoice_type'] = 3;
				$count_data['count'] = $getARC[0]->count + 1;
				$updateIC = Sales::updateARC($count_data);
			}else{
				$add_count_data = array();
				$add_count_data['gstin_id'] = $input['gstin_id'];
				$count_data['invoice_type'] = 3;
				$add_count_data['count'] = '1';
				$addIC = Sales::addARC($add_count_data);
			}

			$invoiceDetailData = array();

			if(is_array($input['total'])){
				foreach ($input['total'] as $key => $value) {
					$invoiceDetailData['invoice_no'] = $input['receipt_no'];
					$invoiceDetailData['invoice_type'] = '3';
					$invoiceDetailData['item_name'] = $input['item_name'][$key];
					$invoiceDetailData['item_value'] = $input['item_value'][$key];
					$invoiceDetailData['item_type'] = "Goods";
					$invoiceDetailData['hsn_sac_no'] = $input['hsn_sac_no'][$key];
					$invoiceDetailData['quantity'] = $input['quantity'][$key];
					$invoiceDetailData['rate'] = $input['rate'][$key];
					$invoiceDetailData['discount'] = $input['discount'][$key];
					$invoiceDetailData['cgst_percentage'] = isset($input['cgst_percentage'][$key]) ? $input['cgst_percentage'][$key] : "0";
					$invoiceDetailData['cgst_amount'] = isset($input['cgst_amount'][$key]) ? $input['cgst_amount'][$key] : "0";
					$invoiceDetailData['sgst_percentage'] = isset($input['sgst_percentage'][$key]) ? $input['sgst_percentage'][$key] : "0";
					$invoiceDetailData['sgst_amount'] = isset($input['sgst_amount'][$key]) ? $input['sgst_amount'][$key] : "0";
					$invoiceDetailData['igst_percentage'] = isset($input['igst_percentage'][$key]) ? $input['igst_percentage'][$key] : "0";
					$invoiceDetailData['igst_amount'] = isset($input['igst_amount'][$key]) ? $input['igst_amount'][$key] : "0";
					$invoiceDetailData['cess_percentage'] = isset($input['cess_percentage'][$key]) ? $input['cess_percentage'][$key] : "0";
					$invoiceDetailData['cess_amount'] = isset($input['cess_amount'][$key]) ? $input['cess_amount'][$key] : "0";
					$invoiceDetailData['total'] = $input['total'][$key];
					$insertInvoiceDetails = Sales::insertInvoiceDetails($invoiceDetailData);
				}
				$returnResponse['status'] = "success";
				$returnResponse['code'] = "201";
				$returnResponse['message'] = "Advance receipt created successfully.";
				$returnResponse['data'] = $insertAdvanceReceipt;
				return $returnResponse;
			}else{
				$invoiceDetailData['invoice_no'] = $input['receipt_no'];
				$invoiceDetailData['invoice_type'] = '3';
				$invoiceDetailData['item_name'] = $input['item_name'];
				$invoiceDetailData['item_type'] = "Goods";
				$invoiceDetailData['hsn_sac_no'] = $input['hsn_sac_no'];
				$invoiceDetailData['quantity'] = $input['quantity'];
				$invoiceDetailData['rate'] = $input['rate'];
				$invoiceDetailData['discount'] = $input['discount'];
				$invoiceDetailData['cgst_percentage'] = isset($input['cgst_percentage']) ? $input['cgst_percentage'] : "0";
				$invoiceDetailData['cgst_amount'] = isset($input['cgst_amount']) ? $input['cgst_amount'] : "0";
				$invoiceDetailData['sgst_percentage'] = isset($input['sgst_percentage']) ? $input['sgst_percentage'] : "0";
				$invoiceDetailData['sgst_amount'] = isset($input['sgst_amount']) ? $input['sgst_amount'] : "0";
				$invoiceDetailData['igst_percentage'] = isset($input['igst_percentage']) ? $input['igst_percentage'] : "0";
				$invoiceDetailData['igst_amount'] = isset($input['igst_amount']) ? $input['igst_amount'] : "0";
				$invoiceDetailData['cess_percentage'] = isset($input['cess_percentage']) ? $input['cess_percentage'] : "0";
				$invoiceDetailData['cess_amount'] = isset($input['cess_amount']) ? $input['cess_amount'] : "0";
				$invoiceDetailData['total'] = $input['total'];
				$insertInvoiceDetails = Sales::insertInvoiceDetails($invoiceDetailData);

				$returnResponse['status'] = "success";
				$returnResponse['code'] = "201";
				$returnResponse['message'] = "Advance receipt created successfully.";
				$returnResponse['data'] = $insertAdvanceReceipt;
				return $returnResponse;
			}
		}else{
			$returnResponse['status'] = "failed";
			$returnResponse['code'] = "400";
			$returnResponse['message'] = "Error while creating invoice. Please try again.";
			$returnResponse['data'] = $insertAdvanceReceipt;
			return $returnResponse;
		}
		return $returnResponse;
	}



	public function advanceReceipt($id){
		$gstin_id = decrypt($id);

		$advanceReceiptData = Sales::advanceReceiptData($gstin_id);
		$getBusinessByGstin = Sales::getBusinessByGstin($gstin_id);
		if(sizeof($advanceReceiptData) > 0){
			$totalSGST = 0;
			$totalCGST = 0;
			$totalIGST = 0;
			$totalCESS = 0;
			$totalValue = 0;
			foreach ($advanceReceiptData as $key => $value) {
				$totalSGST += $value->total_sgst_amount;
				$totalCGST += $value->total_cgst_amount;
				$totalIGST += $value->total_igst_amount;
				$totalCESS += $value->total_cess_amount;
				$totalValue += $value->total_amount;
			}
			$total = array();
			$total['totalTransactions'] = sizeof($advanceReceiptData);
			$total['totalSGST'] = $totalSGST;
			$total['totalCGST'] = $totalCGST;
			$total['totalIGST'] = $totalIGST;
			$total['totalCESS'] = $totalCESS;
			$total['totalValue'] = $totalValue;

			$data = array();
			$data['total'] = $total;
			$data['advanceReceiptData'] = $advanceReceiptData;

			$returnResponse['status'] = "success";
			$returnResponse['code'] = "302";
			$returnResponse['gstin_id'] = $id;
			$returnResponse['business_id'] = $getBusinessByGstin[0]->business_id;
			$returnResponse['message'] = "All Transactions.";
			$returnResponse['data'] = $data;
		}else{
			$returnResponse['status'] = "success";
			$returnResponse['code'] = "204";
			$returnResponse['gstin_id'] = $id;
			$returnResponse['business_id'] = $getBusinessByGstin[0]->business_id;
			$returnResponse['message'] = "No Data Found.";
			$returnResponse['data'] = '';
		}
		return view('sales.advanceReceipt')->with('data', $returnResponse);
	}



	public function cancelAdvanceReceipt($id){
		$getData = Sales::cancelAdvanceReceipt($id);

		if (sizeof($getData) > 0) {
			$returnResponse['status'] = "success";
			$returnResponse['code'] = "200";
			$returnResponse['message'] = "Receipt cncelled successfully.";
			$returnResponse['data'] = $getData;
		}else{
			$returnResponse['status'] = "success";
			$returnResponse['code'] = "204";
			$returnResponse['message'] = "Something went wrong while cancelling note.";
			$returnResponse['data'] = $getData;
		}
		return response()->json($returnResponse);
	}


}