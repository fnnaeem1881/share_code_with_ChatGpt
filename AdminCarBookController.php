<?php


namespace App\Http\Controllers\Admin\CarBook;

use App\AdminUser;
use App\CarBook;
use App\Airlines;
use App\Car;
use App\Airport;
use App\BankManualPayment;
use App\Bid;
use App\BkashManualPayment;
use App\Division;
use App\District;
use App\Thana;
use App\CarBookActionHistory;
use App\CarRental;
use App\CommissionProfitsActivity;
use App\Coupon;
use App\currencyConversion;
use App\DepartureAirport;
use App\FinalSalesReport;
use Barryvdh\DomPDF\Facade as PDF;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\MultiCityTripBooking;
use App\NagadManualPayment;
use App\OperatorCharge;
use App\PartnerBidList;
use App\PartnerCar;
use App\PartnerUser;
use App\PGCurrencyConversion;
use App\RoundTripBooking;
use App\salesReport;
use App\SingleTripBooking;
use Carbon\Carbon;
use DateInterval;
use DateTime;
use PragmaRX\Countries\Package\Countries;
use DateTimeZone;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class AdminCarBookController extends Controller
{
    public $user;
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = Auth::guard('admin')->user();
            return $next($request);
        });
    }

    /**
     * Display a listing of User.
     *
     * @return \Illuminate\Http\Response
     */

    public function todayBook() {
        
        $userType = Auth::guard('admin')->user()->user_type;
        $agent_code = Auth::guard('admin')->user()->agent_code;

        if($userType == 1 or $userType ==2 or $userType == 14) {
            $carbooks = SingleTripBooking::whereDate('created_at', Carbon::today())->latest()->get();
            return view('admin.todayBooking.SingleTripToday', compact('carbooks'));
        } elseif($userType == 3 or $userType == 4) {
            $carbooks = SingleTripBooking::whereDate('created_at', Carbon::today())->where('sd_id', Auth::guard('admin')->user()->id)->orWhere('created_by', $agent_code)->latest()->get();
            return view('admin.todayBooking.SingleTripToday', compact('carbooks'));
        } elseif($userType == 9) {
            $carbooks = SingleTripBooking::whereDate('updated_at', Carbon::today())->where('service_provider', Auth::guard('admin')->user()->id)->orWhere('created_by', $agent_code)->latest()->get();
            return view('admin.todayBooking.SingleTripToday', compact('carbooks'));
        }
    }

    public function getIndex()
    {
        if (is_null($this->user) || !$this->user->can('carbook.view')) {
            abort(403, 'You are Unauthorized to access this page!');
        }

        if (session()->has('success')) {
            toast(Session('success'), 'success');
        }

        if (session()->has('error')) {
            toast(Session('error'), 'error');
        }

        $user_type = Auth::guard('admin')->user()->user_type;
        $user_id = Auth::guard('admin')->user()->id;
        $agent_code = Auth::guard('admin')->user()->agent_code;
        $created_by = Auth::guard('admin')->user()->created_by;
        $airports   = Airport::latest()->get();

        if ($user_type == 1 or $user_type == 2 or $user_type == 14) {
            $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->with('agentSDProfit')->latest()->get();
        } elseif ($user_type == 4) {
            $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', '=', $user_id)->latest()->get();
        } elseif ( $user_type == 5 or $user_type == 6) {
            $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', '=', $created_by)->latest()->get();
        } elseif ($user_type == 3) {
            $agent_code = Auth::guard('admin')->user()->agent_code;
            $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', Auth::guard('admin')->user()->id)->orWhere('created_by', $agent_code)->latest()->get();
        }

        $providers = AdminUser::where('user_type', 9)->latest()->get();
        return view('carbook.index', compact('carBooks','airports','providers'));
    }

    public function Index(Request $request) 
    {
        $can_edit = $can_details  = $can_assign = $can_bid = $can_stop_bid = $live_bids = '';
        if (!auth()->user()->can('carbook.edit')) {
            $can_edit = "style='display:none;'";
        }  
        if (!auth()->user()->can('carbook.details')) {
            $can_details = "style='display:none;'";
        }
        if (!auth()->user()->can('carbook.assign')) {
            $can_assign = "style='display:none;'";
        }

      //  dd($request->all());
        if($request->status_name == 'select' && $request->airport_name == 'select' && $request->startDuration == null && $request->endDuration ==null){
           // dd($request->status_name);
            $user_type = Auth::guard('admin')->user()->user_type;
            $user_id = Auth::guard('admin')->user()->id;
            $created_by = Auth::guard('admin')->user()->created_by;
            $agent_code = Auth::guard('admin')->user()->agent_code;
            $created_by = Auth::guard('admin')->user()->created_by;
            $airports   = Airport::latest()->get();
        
            if ($user_type == 1 or $user_type == 2 or $user_type == 14) {
                $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->orderBy('created_at', 'desc');
            } elseif ($user_type == 4) {
                $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', '=', $user_id)->orderBy('created_at', 'desc');
            } elseif ( $user_type == 5 or $user_type == 6) {
                $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', '=', $created_by)->orderBy('created_at', 'desc');
            } elseif ($user_type == 3) {
                $agent_code = Auth::guard('admin')->user()->agent_code;
                $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', Auth::guard('admin')->user()->id)->orWhere('created_by', $agent_code)->orderBy('created_at', 'desc');
            }
        }
        else{
            //dd($request->all());
            $status_name=$request->status_name;
            $airport_name=$request->airport_name;
            $startDuration=$request->startDuration;
            $endDuration=$request->endDuration;
            $search_filed='created_at';
            if($status_name !='select' && $airport_name == 'select' && $startDuration == null && $endDuration ==null){
               // dd('status');
                $user_type = Auth::guard('admin')->user()->user_type;
                $user_id = Auth::guard('admin')->user()->id;
                $agent_code = Auth::guard('admin')->user()->agent_code;
                $created_by = Auth::guard('admin')->user()->created_by;
                $airports   = Airport::latest()->get();

                if ($user_type == 1 or $user_type == 2 or $user_type == 14) {
                    $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('status',$request->status_name)->orderBy('created_at', 'desc');
                } elseif ($user_type == 4) {
                    $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', '=', $user_id)->where('status',$request->status_name)->orderBy('created_at', 'desc');
                } elseif ( $user_type == 5 or $user_type == 6) {
                    $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', '=', $created_by)->where('status',$request->status_name)->orderBy('created_at', 'desc');
                } elseif ($user_type == 3) {
                    $agent_code = Auth::guard('admin')->user()->agent_code;
                    $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', Auth::guard('admin')->user()->id)->orWhere('created_by', $agent_code)->where('status',$request->status_name)->orderBy('created_at', 'desc');
                }
            }
            if($airport_name !='select' && $status_name == 'select' && $startDuration == null && $endDuration ==null){
              //  dd('airport');
                $user_type = Auth::guard('admin')->user()->user_type;
                $user_id = Auth::guard('admin')->user()->id;
                $agent_code = Auth::guard('admin')->user()->agent_code;
                $created_by = Auth::guard('admin')->user()->created_by;
                $airports   = Airport::latest()->get();

                if ($user_type == 1 or $user_type == 2 or $user_type == 14) {
                    $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('airport_name',$request->airport_name)->orderBy('created_at', 'desc');
                } elseif ($user_type == 4) {
                    $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', '=', $user_id)->where('airport_name',$request->airport_name)->orderBy('created_at', 'desc');
                } elseif ( $user_type == 5 or $user_type == 6) {
                    $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', '=', $created_by)->where('airport_name',$request->airport_name)->orderBy('created_at', 'desc');
                } elseif ($user_type == 3) {
                    $agent_code = Auth::guard('admin')->user()->agent_code;
                    $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', Auth::guard('admin')->user()->id)->orWhere('created_by', $agent_code)->where('airport_name',$request->airport_name)->orderBy('created_at', 'desc');
                }
            }
            if($status_name !='select' && $airport_name  !='select'  && $startDuration == null && $endDuration ==null ){
                //dd('all');
                $user_type = Auth::guard('admin')->user()->user_type;
                $user_id = Auth::guard('admin')->user()->id;
                $agent_code = Auth::guard('admin')->user()->agent_code;
                $created_by = Auth::guard('admin')->user()->created_by;
                $airports   = Airport::latest()->get();

                if ($user_type == 1 or $user_type == 2 or $user_type == 14) {
                    $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('status',$request->status_name)->where('airport_name',$request->airport_name)->orderBy('created_at', 'desc');
                } elseif ($user_type == 4) {
                    $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', '=', $user_id)->where('status',$request->status_name)->where('airport_name',$request->airport_name)->orderBy('created_at', 'desc');
                } elseif ( $user_type == 5 or $user_type == 6) {
                    $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', '=', $created_by)->where('status',$request->status_name)->where('airport_name',$request->airport_name)->orderBy('created_at', 'desc');
                } elseif ($user_type == 3) {
                    $agent_code = Auth::guard('admin')->user()->agent_code;
                    $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', Auth::guard('admin')->user()->id)->orWhere('created_by', $agent_code)->where('status',$request->status_name)->where('airport_name',$request->airport_name)->orderBy('created_at', 'desc');
                }
            }
            if($startDuration !=null && $endDuration == null){
                $startDuration=$request->startDuration.' 00:00:00';
             
              
                $todayDate = Carbon::now();
                $endDuration=$todayDate->toDateTimeString();
                //dd($endDuration);
                if($status_name =='select' && $airport_name  =='select' ){
                    $user_type = Auth::guard('admin')->user()->user_type;
                    $user_id = Auth::guard('admin')->user()->id;
                    $agent_code = Auth::guard('admin')->user()->agent_code;
                    $created_by = Auth::guard('admin')->user()->created_by;
                    $airports   = Airport::latest()->get();
    
                    if ($user_type == 1 or $user_type == 2 or $user_type == 14) {
                        $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->whereBetween($search_filed, [$startDuration, $endDuration])->orderBy('created_at', 'desc');
                    } elseif ($user_type == 4) {
                        $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', '=', $user_id)->whereBetween($search_filed, [$startDuration, $endDuration])->orderBy('created_at', 'desc');
                    } elseif ( $user_type == 5 or $user_type == 6) {
                        $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', '=', $created_by)->whereBetween($search_filed, [$startDuration, $endDuration])->orderBy('created_at', 'desc');
                    } elseif ($user_type == 3) {
                        $agent_code = Auth::guard('admin')->user()->agent_code;
                        $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', Auth::guard('admin')->user()->id)->orWhere('created_by', $agent_code)->whereBetween($search_filed, [$startDuration, $endDuration])->orderBy('created_at', 'desc');
                    }
                  
                }
            }
            if($startDuration ==null && $endDuration != null){
                $pickup_time=SingleTripBooking::first();
                $pickupDateTime=$pickup_time->created_at;
              //  dd($pickupDateTime);
                $startDuration=$pickupDateTime;
                $endDuration=$request->endDuration.' 23:59:59';
               
               // dd($endDuration);
                if($status_name =='select' && $airport_name  =='select' ){
                    $user_type = Auth::guard('admin')->user()->user_type;
                    $user_id = Auth::guard('admin')->user()->id;
                    $agent_code = Auth::guard('admin')->user()->agent_code;
                    $created_by = Auth::guard('admin')->user()->created_by;
                    $airports   = Airport::latest()->get();
    
                    if ($user_type == 1 or $user_type == 2 or $user_type == 14) {
                        $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->whereBetween($search_filed, [$startDuration, $endDuration])->orderBy('created_at', 'desc');
                    } elseif ($user_type == 4) {
                        $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', '=', $user_id)->whereBetween($search_filed, [$startDuration, $endDuration])->orderBy('created_at', 'desc');
                    } elseif ( $user_type == 5 or $user_type == 6) {
                        $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', '=', $created_by)->whereBetween($search_filed, [$startDuration, $endDuration])->orderBy('created_at', 'desc');
                    } elseif ($user_type == 3) {
                        $agent_code = Auth::guard('admin')->user()->agent_code;
                        $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', Auth::guard('admin')->user()->id)->orWhere('created_by', $agent_code)->whereBetween($search_filed, [$startDuration, $endDuration])->orderBy('created_at', 'desc');
                    }
                  
                }
            }
            if($startDuration != null && $endDuration !=null){
                $startDuration=$request->startDuration.' 00:00:00';
                $endDuration=$request->endDuration.' 23:59:59';
                if($status_name =='select' && $airport_name  =='select' ){
                    $user_type = Auth::guard('admin')->user()->user_type;
                    $user_id = Auth::guard('admin')->user()->id;
                    $agent_code = Auth::guard('admin')->user()->agent_code;
                    $created_by = Auth::guard('admin')->user()->created_by;
                    $airports   = Airport::latest()->get();
    
                    if ($user_type == 1 or $user_type == 2 or $user_type == 14) {
                        $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->whereBetween($search_filed, [$startDuration, $endDuration])->orderBy('created_at', 'desc');
                    } elseif ($user_type == 4) {
                        $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', '=', $user_id)->whereBetween($search_filed, [$startDuration, $endDuration])->orderBy('created_at', 'desc');
                    } elseif ( $user_type == 5 or $user_type == 6) {
                        $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', '=', $created_by)->whereBetween($search_filed, [$startDuration, $endDuration])->orderBy('created_at', 'desc');
                    } elseif ($user_type == 3) {
                        $agent_code = Auth::guard('admin')->user()->agent_code;
                        $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', Auth::guard('admin')->user()->id)->orWhere('created_by', $agent_code)->whereBetween($search_filed, [$startDuration, $endDuration])->orderBy('created_at', 'desc');
                    }
                  
                }
            }
            if($status_name !='select' && $airport_name == 'select' && $startDuration != null && $endDuration !=null){
                // dd('status');
                $startDuration=$request->startDuration.' 00:00:00';
                $endDuration=$request->endDuration.' 23:59:59';
                 $user_type = Auth::guard('admin')->user()->user_type;
                 $user_id = Auth::guard('admin')->user()->id;
                 $agent_code = Auth::guard('admin')->user()->agent_code;
                 $created_by = Auth::guard('admin')->user()->created_by;
                 $airports   = Airport::latest()->get();
 
                 if ($user_type == 1 or $user_type == 2 or $user_type == 14) {
                     $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('status',$request->status_name)->whereBetween($search_filed, [$startDuration, $endDuration])->orderBy('created_at', 'desc');
                 } elseif ($user_type == 4) {
                     $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', '=', $user_id)->where('status',$request->status_name)->whereBetween($search_filed, [$startDuration, $endDuration])->orderBy('created_at', 'desc');
                 } elseif ( $user_type == 5 or $user_type == 6) {
                     $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', '=', $created_by)->where('status',$request->status_name)->whereBetween($search_filed, [$startDuration, $endDuration])->orderBy('created_at', 'desc');
                 } elseif ($user_type == 3) {
                     $agent_code = Auth::guard('admin')->user()->agent_code;
                     $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', Auth::guard('admin')->user()->id)->orWhere('created_by', $agent_code)->where('status',$request->status_name)->whereBetween($search_filed, [$startDuration, $endDuration])->orderBy('created_at', 'desc');
                 }
            }

            if($airport_name !='select' && $status_name == 'select' && $startDuration != null && $endDuration !=null){
                //  dd('airport');
                $startDuration=$request->startDuration.' 00:00:00';
                $endDuration=$request->endDuration.' 23:59:59';
                  $user_type = Auth::guard('admin')->user()->user_type;
                  $user_id = Auth::guard('admin')->user()->id;
                  $agent_code = Auth::guard('admin')->user()->agent_code;
                  $created_by = Auth::guard('admin')->user()->created_by;
                  $airports   = Airport::latest()->get();
  
                  if ($user_type == 1 or $user_type == 2 or $user_type == 14) {
                      $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('airport_name',$request->airport_name)->whereBetween($search_filed, [$startDuration, $endDuration])->orderBy('created_at', 'desc');
                  } elseif ($user_type == 4) {
                      $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', '=', $user_id)->where('airport_name',$request->airport_name)->whereBetween($search_filed, [$startDuration, $endDuration])->orderBy('created_at', 'desc');
                  } elseif ( $user_type == 5 or $user_type == 6) {
                      $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', '=', $created_by)->where('airport_name',$request->airport_name)->whereBetween($search_filed, [$startDuration, $endDuration])->orderBy('created_at', 'desc');
                  } elseif ($user_type == 3) {
                      $agent_code = Auth::guard('admin')->user()->agent_code;
                      $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', Auth::guard('admin')->user()->id)->orWhere('created_by', $agent_code)->where('airport_name',$request->airport_name)->whereBetween($search_filed, [$startDuration, $endDuration])->orderBy('created_at', 'desc');
                  }
            }

            if($status_name !='select' && $airport_name  !='select'  && $startDuration != null && $endDuration !=null ){
                //dd('all');
                $startDuration=$request->startDuration.' 00:00:00';
                $endDuration=$request->endDuration.' 23:59:59';
                $user_type = Auth::guard('admin')->user()->user_type;
                $user_id = Auth::guard('admin')->user()->id;
                $agent_code = Auth::guard('admin')->user()->agent_code;
                $created_by = Auth::guard('admin')->user()->created_by;
                $airports   = Airport::latest()->get();

                if ($user_type == 1 or $user_type == 2 or $user_type == 14) {
                    $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('status',$request->status_name)->where('airport_name',$request->airport_name)->whereBetween($search_filed, [$startDuration, $endDuration])->orderBy('created_at', 'desc');
                } elseif ($user_type == 4) {
                    $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', '=', $user_id)->where('status',$request->status_name)->where('airport_name',$request->airport_name)->whereBetween($search_filed, [$startDuration, $endDuration])->orderBy('created_at', 'desc');
                } elseif ( $user_type == 5 or $user_type == 6) {
                    $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', '=', $created_by)->where('status',$request->status_name)->where('airport_name',$request->airport_name)->whereBetween($search_filed, [$startDuration, $endDuration])->orderBy('created_at', 'desc');
                } elseif ($user_type == 3) {
                    $agent_code = Auth::guard('admin')->user()->agent_code;
                    $carBooks = SingleTripBooking::where('payment_status', "!=", 'Due')->where('sd_id', Auth::guard('admin')->user()->id)->orWhere('created_by', $agent_code)->where('status',$request->status_name)->where('airport_name',$request->airport_name)->whereBetween($search_filed, [$startDuration, $endDuration])->orderBy('created_at', 'desc');
                }
            }
           // dd($request->all());
            
        }
        return DataTables::of($carBooks)
            ->addIndexColumn()
            ->addColumn('to', function($row){
                if($row->city_name != null)
                return $row->city_name;
                else return $row->thana_name.'    ,'.$row->district_name;
            })
            ->addColumn('airport_name', function($row){
                if($row->trip_type == 0)
                    return $row->airport_name;
                elseif($row->trip_type == 1)
                    return $row->division_name. ' ,'. $row->district_name. ','. $row->thana_name;
             })
             ->addColumn('to', function($row){
                if($row->trip_type == 0)
                    return $row->division_name. ' ,'. $row->district_name. ','. $row->thana_name;
                elseif($row->trip_type == 1)
                    return $row->airport_name;
            })
            ->addColumn('agent_commission', function($row){
                if( $agentCommission=CommissionProfitsActivity::where('booking_id',$row->booking_id)->select('agent_commission')->first())
                 return $agentCommission->agent_commission;
             })
             ->addColumn('sd_profit', function($row){
                if( $sdProfit=CommissionProfitsActivity::where('booking_id',$row->booking_id)->select('sd_profit')->first())
                 return $sdProfit->sd_profit;
             })
             ->addColumn('C_P_Status', function($row){
                if( $sdProfit=CommissionProfitsActivity::where('booking_id',$row->booking_id)->select('status')->first())
                 return $sdProfit->status;
             })
            ->addColumn('service_provider', function($row){
               if( $providers=AdminUser::where('id',$row->service_provider)->select('username')->first())
                return $providers->username;
            })
            ->addColumn('currency', function($row){
               if( $currency=AdminUser::where('agent_code',$row->created_by)->select('currency')->first())
                return $currency->currency;
            })
             ->addColumn('booking_date_time', function($row){
               if(  $bookDateTime = new \Datetime($row->created_at, new DateTimeZone('Asia/Dhaka')))
                return $bookDateTime->format('l d F Y, h:i A');
            }) 
            ->addColumn('pick_date_time', function($row){
               if( $pickupDateTime = new \Datetime($row->pickup_date_time, new DateTimeZone('Asia/Dhaka')))
                return $pickupDateTime->format('l d F Y, h:i A');
            })
            ->addColumn('time_left', function($row){
                if( $pickupDateTime = new \Datetime($row->pickup_date_time, new DateTimeZone('Asia/Dhaka')) > $date = new DateTime('now', new DateTimeZone('Asia/Dhaka')))
                if( $pickupDateTime = new \Datetime($row->pickup_date_time, new DateTimeZone('Asia/Dhaka')) )
                return $pickupDateTime->diff($date)->days * 24 + $pickupDateTime->diff($date)->h . ' hours';
                else return '';
                else return 'Expired' ;
            })
            ->addColumn('payment_method', function($row){
                if ($row->payment_method == 'bkash') {
                    $bkash=BkashManualPayment::where('booking_id',$row->booking_id)->first();
                    if($bkash != null) {
                        $bkashs=BkashManualPayment::where('booking_id',$row->booking_id)->get();
                        $array = [];
                        foreach($bkashs as $item) {
                            $trxID = $row->payment_method . '(Transaction ID:' .$item->trxID. ')';
                            array_unshift($array,$trxID);
                        }
                        return $array;
                    } else return $row->payment_method;
                } elseif ($row->payment_method == 'bank') {
                    $bank=BankManualPayment::where('booking_id',$row->booking_id)->first();
                    $array = [];
                    if($bank != null) {
                        $banks=BankManualPayment::where('booking_id',$row->booking_id)->get();
                        foreach($banks as $item) {
                            $trxID = $row->payment_method . '(Transaction ID:' .$item->trxID. ')';
                            array_unshift($array,$trxID);
                        }
                        return $array;
                    } else return $row->payment_method;
                }elseif ($row->payment_method == 'nagad') {
                    $nagad=NagadManualPayment::where('booking_id',$row->booking_id)->first();
                    $array = [];
                    if($nagad != null) {
                        $nagads=NagadManualPayment::where('booking_id',$row->booking_id)->get();
                        foreach($nagads as $item) {
                            $trxID = $row->payment_method . '(Transaction ID:' .$item->trxID. ')';
                            array_unshift($array,$trxID);
                        }
                        return $array;
                    } else return $row->payment_method;
                } else {
                    return $row->payment_method;
                }
            })
            ->addColumn('driver_information', function($row){
               if( $row->driver_name != '')
                    return
                    $row->driver_name. "<br>".
                    ' mob:'  .$row->driver_mobile."<br>".
                    ' nid:' .$row->driver_nid. "<br>".
                    ' car :'  .$row->car_no;
            })
            ->addColumn('action', function($row) use ($can_edit, $can_details, $can_assign, $can_bid, $can_stop_bid, $live_bids){
                if ($row->payment_status == 'Paid' or $row->payment_status == "Advance Paid" && $row->service_provider == 0) {
                    $paid = "style='display:block;'";
                }else{
                    $paid = "style='display:none;'";
                }

                $bid = PartnerBidList::where('booking_id', $row->booking_id)->first();


                if($bid == null or $bid->status == 0) {
                    $can_bid = "style='display:none;'"; // in production time display will be block now display:none
                    $can_stop_bid = "style='display:none;'";
                    $live_bids = "style='display:none;'";
                } elseif($bid->status == 2) {
                    $can_bid = "style='display:none;'";
                    $can_stop_bid = "style='display:none;'";
                    $can_assign = "style='display:none;'";
                    $live_bids = "style='display:none;'";
                } elseif($bid->status == 4) {
                    $can_bid = "style='display:none;'";
                    $can_stop_bid = "style='display:none;'";
                    $can_assign = "style='display:none;'";
                    $live_bids = "style='display:none;'";
                }elseif($bid->status == 5) {
                    $can_bid = "style='display:none;'";
                    $can_stop_bid = "style='display:none;'";
                    $can_assign = "style='display:none;'";
                    $live_bids = "style='display:none;'";
                }elseif($bid->status == 6) {
                    $can_bid = "style='display:none;'";
                    $can_stop_bid = "style='display:none;'";
                    $can_assign = "style='display:none;'";
                    $live_bids = "style='display:none;'";
                } elseif($bid->status == 7) {
                    $can_bid = "style='display:none;'";
                    $can_stop_bid = "style='display:none;'";
                    $can_assign = "style='display:none;'";
                    $live_bids = "style='display:none;'";
                } else {
                    $can_bid = "style='display:none;'";

                    if($bid->status == 1) {
                        $can_stop_bid = "style='display:block;'";
                        $live_bids = "style='display:block;'";
                        $can_assign = "style='display:none;'";
                    }
                }
        
                return
                    '<small><b>'.$row->status.'</b></small>
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button"
                            id="dropdownMenu2" data-toggle="dropdown" aria-haspopup="true"
                            aria-expanded="false">
                            Action
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenu2">
                            <a '.$can_edit.' href='.url('booking/edit/').'/'.Crypt::encryptString($row->booking_id).' class="dropdown-item" 
                            >Edit</a>
                            <a class="dropdown-item assign-provider" '.$can_assign.''.$paid.' data-toggle="modal" data-target="#assign-service-provider-modal'.$row->id.'" data-url='.url('booking/assign/').'/'.Crypt::encryptString($row->booking_id).' href='.url('booking/edit/').'/'.$row->id.'>Assign Service Provider</a>
                            <a class="dropdown-item assign-provider" '.$can_bid.' data-toggle="modal" data-target="#assign-bid-modal'.$row->id.'" data-url='.url('single-trip/start/bidding/').'/'.Crypt::encryptString($row->booking_id).' href='.url('booking/edit/').'/'.$row->id.'>Start Bidding</a>
                            <a class="dropdown-item" '.$can_stop_bid.' href='.url('single-trip/cancel/bidding/').'/'.Crypt::encryptString($row->booking_id).'>Cancel Bidding</a>
                            <a class="dropdown-item" '.$live_bids.' href='.url('single-trip/live/bids/').'/'.Crypt::encryptString($row->booking_id).' target="_blank">Live Bids</a>
                            <a class="dropdown-item" '.$can_details.' href='.url('booking/details/').'/'.Crypt::encryptString($row->booking_id).' >Details</a>
                            <a class="dropdown-item" '.$can_details.' href='.url('user/MyBooking/print/').'/'.Crypt::encrypt($row->booking_id).'  target="_blank">Print</a>
                        </div>
                    </div>';
            })
            ->addColumn('discount_amount', function($row){
                if ($row->discount == null)
                    return 0;
                else return $row->discount ;
            })
            ->rawColumns(['action','driver_information', 'payment_method'])
            ->make(true);
    }
    
    public function getStatusToAirport(Request $request)
    {
        $data   = Airport::latest()->select('name')->get();
        if (count($data) > 0) {
            echo '<option value="">Select Airport</option>';
            foreach ($data as $row => $value) {
                print '<option value="' . $value->name .'" >' . $value->name . '</option>';
            }
        } else {
            echo"<option></option>";
        }
    }
    public function getCreate()
    {
        if (is_null($this->user) || !$this->user->can('carbook.create')) {
            abort(403, 'You are Unauthorized to create user!');
        }

        $airports   = Airport::latest()->get();
        $cars       = Car::latest()->get();
        $divisions  = Division::latest()->get();
        $districts  = District::latest()->get();
        $thanas     = Thana::latest()->get();
        $nationalities = NATIONALITY_LIST;
        $airlines   = Airlines::latest()->get();
        $Countries  = new Countries();
        $countries  = $Countries->all();
        $statusList = BOOKING_STATUS;

        return view('carbook.create', compact('airports', 'cars', 'divisions', 'districts', 'thanas', 'nationalities', 'airlines', 'countries', 'statusList'));
    }

    public function getDivision(Request $request)
    {
        $getDivisions = Division::where('division', $request->division)->get();
        return view('carbook.district_select', compact('getDivisions'));
    }

    public function getDistrict(Request $request)
    {
        $getDistricts = District::where('district_id', $request->district_id)->get();
        return view('admin.shipping_area.state.state_select', compact('getDistricts'));
    }

    public function getDetails(Request $request ,$booking_id)
    {
        if(strlen($booking_id) < 15){
            $id='#'.$booking_id;
        }else{
            $id = Crypt::decryptString($booking_id);
        }
        
        $SingleTrip = SingleTripBooking::where('booking_id', $id)->first();
        $RoundTrip = RoundTripBooking::where('booking_id', $id)->first();
        $MulticityTrip = MultiCityTripBooking::where('booking_id', $id)->first();
        $CarRental = CarRental::where('booking_id', $id)->first();

        if($SingleTrip !=null){
            $carBook=$SingleTrip;
        }elseif($RoundTrip !=null){
            $carBook=$RoundTrip;
        }elseif($MulticityTrip !=null){
            $carBook=$MulticityTrip;
        }elseif($CarRental !=null){
            $carBook=$CarRental;
            $pickupDateTime = new \Datetime($carBook->pickup_date_time);
            $pickupDateTimeStr = $pickupDateTime->format('l d F Y, h:i A');
            $bookedBy = $carBook->user->username;
            if($this->user->user_type == 4){
                if($carBook->sd_id == $this->user->id){
                    return view('carbook.carRentalDetails', compact('carBook', 'pickupDateTimeStr', 'bookedBy'));
                }else{
                    return redirect()->route('carbook')->with('error','You are Unauthorized to access this Booking!');
                }
            }elseif($this->user->user_type == 5 or $this->user->user_type == 6){
                if($carBook->sd_id == $this->user->created_by){
                    return view('carbook.carRentalDetails', compact('carBook', 'pickupDateTimeStr', 'bookedBy'));
                }else{
                    return redirect()->route('carbook')->with('error','You are Unauthorized to access this Booking!');
                }
            }else{
                return view('carbook.carRentalDetails', compact('carBook', 'pickupDateTimeStr', 'bookedBy'));
            }
        }

        $pickupDateTime = new \Datetime($carBook->pickup_date_time);
        $pickupDateTimeStr = $pickupDateTime->format('l d F Y, h:i A');

        $DOB = new \Datetime($carBook->dob);
        $dobStr = $DOB->format('d/m/Y');
        $commissionProfitActivities = CommissionProfitsActivity::where('booking_id', $carBook->booking_id)->first();

        $bookedBy = $carBook->user;

        $flightDate = new \Datetime($carBook->flight_date);
        $flightDateStr = $flightDate->format('d/m/Y');

        if($this->user->user_type == 4){
          if($carBook->sd_id == $this->user->id){
            return view('carbook.details', compact('carBook', 'pickupDateTimeStr', 'dobStr', 'flightDateStr', 'bookedBy'));
          }else{
                return redirect()->route('carbook')->with('error','You are Unauthorized to access this Booking!');
          }
        }elseif($this->user->user_type == 5 or $this->user->user_type == 6){
            if($carBook->sd_id == $this->user->created_by){
                return view('carbook.details', compact('carBook', 'pickupDateTimeStr', 'dobStr', 'flightDateStr', 'bookedBy'));
              }else{
                    return redirect()->route('carbook')->with('error','You are Unauthorized to access this Booking!');
              }
        }else{
            $payments = null;
            $payment = "";

            if($carBook->payment_method == "bank") {
                $payment = BankManualPayment::where('booking_id', $carBook->booking_id)->first();
                $payments = BankManualPayment::where('booking_id', $carBook->booking_id)->get();
                $paymentcount = BankManualPayment::where('booking_id', $carBook->booking_id)->count();
            }

            if($carBook->payment_method == "bkash") {
                $payment = BkashManualPayment::where('booking_id', $carBook->booking_id)->first();
                $payments = BkashManualPayment::where('booking_id', $carBook->booking_id)->get();
                $paymentcount= BkashManualPayment::where('booking_id', $carBook->booking_id)->count();
            }

            if($carBook->payment_method == "nagad") {
                $payment = NagadManualPayment::where('booking_id', $carBook->booking_id)->first();
                $payments = NagadManualPayment::where('booking_id', $carBook->booking_id)->get();
                $paymentcount = NagadManualPayment::where('booking_id', $carBook->booking_id)->count();
            }

            if($carBook->payment_method == "wallet") {
                $payment = null;
                $paymentcount = 0;
            }
            
            return view('carbook.details', compact('carBook', "payment", "payments", 'commissionProfitActivities', 'pickupDateTimeStr', 'dobStr', 'flightDateStr', 'bookedBy'));
        }
    }

    public function getEdit(Request $request, $booking_id)
    {
        if (is_null($this->user) || !$this->user->can('carbook.edit')) {
            abort(403, 'You are Unauthorized to edit carbook!');
        }
        
        if(strlen($booking_id) < 15){
            $id='#'.$booking_id;
        }else{
            $booking_id = Crypt::decryptString($booking_id);
        }

        $carBook = SingleTripBooking::where('booking_id', $booking_id)->first();
        $pickupDateTime = new \Datetime($carBook->pickup_date_time);
        $pickupDateTimeStr = $pickupDateTime->format('Y-m-d');
        $pickupTimeStr = $pickupDateTime->format('H:i');
        $pickupDateTimee = $carBook->pickup_date_time;
        $pickup_date_timerr=$pickupDateTimeStr.'T'.$pickupTimeStr;
       
        $date = new \DateTime("now", new DateTimeZone('Asia/Dhaka'));
        // echo $date->format('Y-m-d H:i:s');
        $interval = $pickupDateTime->diff($date);

        $diff = ($interval->days * 24) + $interval->h;

        $DOB = new \Datetime($carBook->dob);
        $dobStr = $DOB->format('d/m/Y');

        $created_by = $carBook->created_by;
        $currency=AdminUser::where('agent_code',$created_by)->select('currency')->first();
        $currency = $currency->currency;

        $flightDate = new \Datetime($carBook->flight_date);
        $flightDateStr = $flightDate->format('d/m/Y');

        $airlines = Airlines::get()->pluck('airlines_name', 'airlines_name');
        $Countries = new Countries();
        $countries = $Countries->all()->pluck('name.common')->toArray();
        $nationalities = NATIONALITY_LIST;
        $titles = NAME_TITLES;
        $statusList = BOOKING_STATUS;
        $carName = Car::select('name')->distinct()->get();
        $carType = Car::select('car_type')->distinct()->get();
        $airports = Airport::get()->pluck('name', 'name');

        $divisions = Division::get()->pluck('division_name', 'division_name');
        $districts = [];
        $thanas = [];
        if (isset($carBook->division_name)) {
            $districts = District::where('divisions_id', $carBook->division_name)->get()->pluck('district_name', 'district_name');
        }

        if (isset($carBook->district_name)) {
            $thanas = Thana::where('district_name', $carBook->district_name)->get()->pluck('thana_name', 'thana_name');
        }

        $DepartureAirport=DepartureAirport::all();
        $commissionProfitActivities = CommissionProfitsActivity::where('booking_id', $carBook->booking_id)->first();

        $payment = "";
        $paymentcount = 0;

        if($carBook->payment_method == "bank") {
            $payment = BankManualPayment::where('booking_id', $carBook->booking_id)->first();
            $paymentcount = BankManualPayment::where('booking_id', $carBook->booking_id)->count();
        }

        if($carBook->payment_method == "bkash") {
            $payment = BkashManualPayment::where('booking_id', $carBook->booking_id)->first();
            $paymentcount= BkashManualPayment::where('booking_id', $carBook->booking_id)->count();
        }

        if($carBook->payment_method == "nagad") {
            $payment = NagadManualPayment::where('booking_id', $carBook->booking_id)->first();
            $paymentcount = NagadManualPayment::where('booking_id', $carBook->booking_id)->count();
        }

        if($carBook->payment_method == "wallet") {
            $payment = null;
            $paymentcount = 0;
        }

        return view('carbook.edit', compact('carBook', 'payment', 'paymentcount', 'commissionProfitActivities', 'created_by', 'pickupDateTimeStr', 'pickupTimeStr','carType', 'currency', 'carName', 'airports', 'divisions', 'districts', 'thanas', 'airlines', 'countries', 'titles', 'nationalities', 'pickupDateTimeStr', 'dobStr','pickup_date_timerr', 'flightDateStr', 'statusList','DepartureAirport'));
    }

    public function putUpdate(Request $request, SingleTripBooking $carBook)
    {
        if (is_null($this->user) || !$this->user->can('carbook.view')) {
            abort(403, 'You are Unauthorized to edit carbook!');
        }

        $user=AdminUser::where('agent_code',$carBook->created_by)->first();
        $wallet_balance= $user->wallet_balance;
        $old_fair=$carBook->fare;
        $subtotal= $request->subtotal;
        $pickup_date_time = $request->pickupdate.' '.$request->pickuptime;
        $phpdate = date_parse( $pickup_date_time );

        $pickupDateTimeFormate = $phpdate['year'].'-'.$phpdate['month'].'-'.$phpdate['day'].' '.$phpdate['hour'].':'.$phpdate['minute'].':'.$phpdate['second'];

        if($subtotal > $old_fair){
            $new_subtotal= $subtotal - $old_fair;
        
            if($wallet_balance < $new_subtotal){
                return  2;
            }
            $new_wallet_balance= $wallet_balance - $new_subtotal;
        }
        elseif($subtotal < $old_fair){
            $new_subtotal = $old_fair - $subtotal;
            $new_wallet_balance= $wallet_balance + $new_subtotal;
        }

        if(isset($new_wallet_balance)){
            AdminUser::where('agent_code',$carBook->created_by)->update(['wallet_balance' => $new_wallet_balance]);
        }
        $todayDate = Carbon::now();
        $CurrentDate=Carbon::parse($todayDate . '  Asia/Dhaka')->tz('UTC');
        $updated_at=$CurrentDate->toDateTimeString();
        
        $flight_time = str_replace(" ", "", $request->flight_time);
        $departure_time = str_replace(" ", "", $request->departure_time);
        
        $carBook->car_name              = $request->car_name;
        $carBook->pickup_date_time      = $pickupDateTimeFormate;
        $carBook->airport_name          = $request->airport_name;
        $carBook->division_name         = $request->division_name;
        $carBook->district_name         = $request->district_name;
        $carBook->thana_name            = $request->thana_name;
        $carBook->village_name          = $request->village_name;
        $carBook->no_of_passenger       = (int)$request->no_of_passenger ?? 0;
        $carBook->fare                  = $request->fare ?? 0;
        $carBook->subtotal              = $subtotal ?? 0;
        $carBook->status                = $request->status ?? $carBook->status;
        $carBook->full_name             = $request->full_name;
        $carBook->passport_no           = $request->passport_no ?? null;
        $carBook->nationality           = $request->nationality ?? null;
        $carBook->phone_no              = $request->phone_no;
        $carBook->email                 = $request->email;
        $carBook->airlines_name         = $request->airlines_name;
        $carBook->flight_number         = $request->flight_number;
        $carBook->departure_airport     = $request->departure_airport;
        $carBook->ticket_number         = $request->ticket_number ?? null;
        $carBook->emergency_contact     = $request->emergency_contact ?? null;
        $carBook->note                  = $request->note;

        if($request->payment_method == null) {
            $paymentMethod = $carBook->payment_method;
        } else {
            $paymentMethod = $request->payment_method;
        }
        
        if($request->transaction_id != null) {
            
            if($paymentMethod == "bank") {
                $bank               = new BankManualPayment();
                $bank->booking_id   = $request->booking_id;
                $bank->amount       = $request->adb_pgw_subtotal;
                $bank->trxID        = $request->transaction_id;
                $bank->status       = "Accepted";
                $bank->updated_by   = Auth::guard('admin')->user()->id;
                $bank->save();
            }

            if($paymentMethod == "bkash" ) {
                $bkash              = new BkashManualPayment();
                $bkash->booking_id  = $request->booking_id;
                $bkash->amount      = $request->adb_pgw_subtotal;
                $bkash->trxID       = $request->transaction_id;
                $bkash->status      = "Accepted";
                $bkash->updated_by   = Auth::guard('admin')->user()->id;
                $bkash->save();
            }

            if($paymentMethod == "nagad" ) {
                $nagad              = new NagadManualPayment();
                $nagad->booking_id  = $request->booking_id;
                $nagad->amount      = $request->adb_pgw_subtotal;
                $nagad->trxID       = $request->transaction_id;
                $nagad->status      = "Accepted";
                $nagad->updated_by   = Auth::guard('admin')->user()->id;
                $nagad->save();
            }
        }

        if($request->payment_status != null) {
            $carBook->payment_status        = $request->payment_status;
        }

        if($request->payment_method != null) {
            $carBook->payment_method        = $request->payment_method;
        }

        if($request->adb_pgw_subtotal != null & $request->adb_pgw_subtotal != $carBook->adb_pgw_subtotal) {
            $carBook->adb_pgw_subtotal      = $carBook->adb_pgw_subtotal + $request->adb_pgw_subtotal;
            $carBook->payment_recieved_bdt  = $carBook->adb_pgw_subtotal. "(". $carBook->payment_status .")";
        }
        
        $carBook->updated_at            = $updated_at;
        $carBook->updated_by            = Auth::guard('admin')->user()->id;
        $carBook->save();       

        $created_by = $carBook->created_by;
        $user = AdminUser::where('agent_code', $created_by)->select('currency', 'country', 'id', 'user_type')->first();
        $currency = $user->currency;
        $country = $user->country;
        $user_type = $user->user_type;
        $convRate = PGCurrencyConversion::where('base_currency', $currency)->where('converted_currency', 'BDT')->select('conversion_rate')->first();
        $bdtConv = $convRate->conversion_rate;

        if((float)$request->discount != $carBook->discount){
            $carBook->coupon_code = $request->coupon_code;
            $carBook->discount = (float)$request->discount;
            $final_subtotal = $carBook->fare - $carBook->discount;
            $carBook->subtotal = $final_subtotal;
            $carBook->bdt_fare = round($final_subtotal * $bdtConv);

            //update coupon
            $coupon = Coupon::where('coupon_code', $request->coupon_code)->first();
            $coupon->flag = 0;
            $coupon->status = 0;
            $coupon->save();
            $carBook->save();
        };


        if($request->vendor_sale != null) {
            $carBook->vendor_sale = $request->vendor_sale;
            
            if($user_type == 7 or $user_type == 8) {
                $commission = CommissionProfitsActivity::where('booking_id',$carBook->booking_id)->select('agent_commission', 'sd_profit')->first();
                if($commission == null) {
                    return 3;
                }
                $agent_commission = round($commission->agent_commission * $bdtConv);
                $sd_profit = round($commission->sd_profit * $bdtConv);
            } else {
                $agent_commission = 0;
                $sd_profit = 0;
            }
            
            $discount = round($carBook->discount * $bdtConv);
            $fare = round($carBook->fare * $bdtConv);
            $total_cost = $fare - $discount;
            $operatorCharge = OperatorCharge::where('country', $country)->where('status', 1)->first();
            
            $finalSalesReport = FinalSalesReport::where('booking_id', $carBook->booking_id)->first();

            if($finalSalesReport == null) {
                $finalSalesReport                       = new FinalSalesReport();
            }

            $finalSalesReport->booking_id               = $carBook->booking_id;
            $finalSalesReport->sd_id                    = $carBook->sd_id;
            $finalSalesReport->booked_by                = $user->id;
            $finalSalesReport->pickup_date_time         = $carBook->pickup_date_time;
            $finalSalesReport->booking_date_time        = $carBook->created_at;
            $finalSalesReport->fare                     = $fare;
            $finalSalesReport->discount_amount          = $discount;
            $finalSalesReport->subtotal                 = $total_cost;
            $finalSalesReport->vendor_sale              = $request->vendor_sale;
            $finalSalesReport->operator_charge          = $carBook->operator_charge; // pore
            $finalSalesReport->agent_commission         = $agent_commission;
            $finalSalesReport->sd_profit                = $sd_profit;
            $finalSalesReport->country                  = $country; // pore
            $finalSalesReport->payment_method           = $carBook->payment_method;
            $finalSalesReport->payment_status           = $carBook->payment_status;
            $finalSalesReport->booking_status           = $carBook->status;


            if($user_type == 7 or $user_type == 8) {
                $value = (float)$fare - (float)$request->vendor_sale - (float)$agent_commission - (float)$sd_profit;
            } elseif($user_type == 10 or $user_type == 50) {
                $value = (float)$fare - (float)$request->vendor_sale;
            } 

            if($operatorCharge != null) {
                $operatorAmount = $operatorCharge->amount;
                if($operatorCharge->type == "amount") {
                    $subtotal = round($value - $operatorAmount);
                    $gbProfit = $subtotal - $discount;
                    $finalSalesReport->operator_charge = $operatorAmount;
                } elseif($operatorCharge->type == "percentage") {
                    $operatorChargeAmount = ($value*$operatorAmount)/100;
                    $subtotal = round($value - $operatorChargeAmount);
                    $gbProfit = $subtotal-$discount;
                    $finalSalesReport->operator_charge = $operatorChargeAmount;
                }
            } else {
                $operatorAmount = 0;
                $subtotal = round($value - $operatorAmount);
                $gbProfit = $subtotal - $discount;                
                $finalSalesReport->operator_charge = $operatorAmount;
            }

            $finalSalesReport->gb_profit = $gbProfit;
            $carBook->gb_profit = $gbProfit;
            
            if($user_type == 7 or $user_type == 8) {
                $cpStatus=CommissionProfitsActivity::where('booking_id',$carBook->booking_id)->select('status')->first();
                $finalSalesReport->cp_status = $cpStatus->status;
                if($carBook->status == "Customer Dropped" && $carBook->payment_status == "Paid") {
                    $finalSalesReport->status = 1;
                    if($finalSalesReport->flag_date == null) {

                        // formating date =====
                        $todayDate = Carbon::now();
                        $CurrentDate=Carbon::parse($todayDate . '  Asia/Dhaka')->tz('UTC');
                        $CurrentDate->add(new DateInterval('PT' . 6 . 'H'));
                        $flag_date=$CurrentDate->toDateTimeString();

                        $finalSalesReport->flag_date = $flag_date;
                        $finalSalesReport->flag = 1;
                    }
                } else {
                    $finalSalesReport->status = 0;
                    $finalSalesReport->flag = 0;
                }
            } elseif($user_type == 10 or $user_type == 50) {
                if($carBook->status == "Customer Dropped" && $carBook->payment_status == "Paid") {
                    $finalSalesReport->status = 1;
                    $finalSalesReport->cp_status = "N/A";

                    if($finalSalesReport->flag_date == null) {
                        // formating date =====
                        $todayDate = Carbon::now();
                        $CurrentDate=Carbon::parse($todayDate . '  Asia/Dhaka')->tz('UTC');
                        $CurrentDate->add(new DateInterval('PT' . 6 . 'H'));
                        $flag_date=$CurrentDate->toDateTimeString();

                        $finalSalesReport->flag_date = $flag_date;
                        $finalSalesReport->flag = 1;
                    }
                } else {
                    $finalSalesReport->cp_status = "N/A";
                    $finalSalesReport->status = 0;
                    $finalSalesReport->flag = 0;
                }
            }
            $finalSalesReport->save();
            $carBook->save();    
        }


        if($request->payment_reference_status) {
            $commission_profits_activities = CommissionProfitsActivity::where('booking_id', $carBook->booking_id)->first();
            $commission_profits_activities->status = $request->payment_reference_status;
            $commission_profits_activities->updated_by = Auth::guard('admin')->user()->id;
            $commission_profits_activities->save();
        }

        // Save to Action History Table
        CarBookActionHistory::create([
            'garibook_id'   => $carBook->id,
            'action'        => 'Updated',
            'by_user'       => Auth::user()->id,
            'action_data'   => $carBook->toJson(),
        ]);

        return 1;
        // return redirect()->route('users.index')->with('warning', "The $user->name was updated successfully");
    }

    /**
     * Remove User from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // $user = User::findOrFail($id);
        // $user->delete();

        // return redirect()->route('users.index')->with('danger', "The $user->name was deleted successfully");
    }

    /**
     * Delete all selected User at once.
     *
     * @param Request $request
     */
    public function massDestroy(Request $request)
    {
        // if ($request->input('ids')) {
        //     $entries = User::whereIn('id', $request->input('ids'))->get();

        //     foreach ($entries as $entry) {
        //         $entry->delete();
        //     }
        // }
    }

    /**
     * Assign a Service Provider Form for a Carbooking
     *
     * @param Illuminate\Http\Request $request
     * @param App\CarBook $carBook
     * @return \Illuminate\Http\Response
     */
    public function getAssignProvider(Request $request, CarBook $carBook)
    {
        if (is_null($this->user) || !$this->user->can('carbook.approve')) {
            abort(403, 'You are Unauthorized to assign carbook provider!');
        }
        $providers = [];
        foreach (AdminUser::where('user_type', 9)->get() as $user) {
            $providers[$user->id] = $user->username;
        }
        return view('carbook.assign', compact('carBook', 'providers'));
    }
    

    /**
     * Assign a Service Provider for a Carbooking
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function postAssignProviderNow(Request $request)
    {

        if (is_null($this->user) || !$this->user->can('carbook.approve')) {
            abort(403, 'You are Unauthorized to assign carbook provider!');
        }

        $booking_id = $request->id;
        $carBook = SingleTripBooking::findOrFail($booking_id);

        $serviceProvider = AdminUser::find($request->provider);

        if ($carBook instanceof SingleTripBooking and $serviceProvider instanceof AdminUser and $serviceProvider->user_type == 9) {
            $carBook->service_provider = $request->provider;
            $carBook->updated_by = Auth::guard('admin')->user()->id;
            $carBook->driver_name = $request->driver_name;
            $carBook->driver_mobile = $request->driver_mobile;
            $carBook->driver_nid = $request->driver_nid;
            $carBook->car_no = $request->car_no;
            $carBook->status = 'Assigned';
            if($carBook->save()) {
                $singleTripBookingUpdateService = SingleTripBooking::find($booking_id)->update(['status' => "Assigned"]);
            }

            // Save to Action History Table
            CarBookActionHistory::create([
                'garibook_id' => $carBook->id,
                'action' => 'Assign Service Provider',
                'by_user' => Auth::guard('admin')->user()->id,
                'action_data' => $carBook->toJson(),
            ]);

            $email = $serviceProvider->email;
            $SPname = $serviceProvider->usename;
    
            $pickupDateTime     = new \Datetime($carBook->pickup_date_time, new DateTimeZone('Asia/Dhaka'));
            $pickupDateTimeStr  = $pickupDateTime->format('l d F Y');

            $DOB    = new \Datetime($carBook->dob);
            $dobStr = $DOB->format('d/m/Y');

            $flightDate     = new \Datetime($carBook->flight_date);
            $flightDateStr  = $flightDate->format('d/m/Y');
            $storeBooking        = $carBook;
            
            $name   = $carBook->first_name;
            $pdf    = PDF::loadview('garibook.print', compact( 'carBook','storeBooking', 'pickupDateTimeStr', 'dobStr', 'flightDateStr'));
            $pdf_name='e-ticket-SP'.$carBook->booking_id.'.pdf';


            Storage::put('app/public/tickets/'.$pdf_name, $pdf->output());
            $send_mail = storage_path('app/app/public/tickets/'.$pdf_name);



            // \Mail::send('garibook.provider.providerMail', compact('carBook',), function ($message) use ($SPname, $SPemail, $carBook) {
            // $pdf = PDF::loadview('garibook.print', compact('carBook', 'pickupDateTimeStr', 'dobStr', 'flightDateStr'));
            //  $message->to($SPemail, $SPname)->subject('Garibook.com: New Booking Notification.');
            //  $message->attachData($pdf->output(), 'filename.pdf');
            //  });
            // $job = (new SendEmailJob($email)); // issue here
            // dispatch($job);
                return redirect()->back()->with('success','Service Provider has been assigned successfully.');
            // return response()->json([
            //     'success' => 'true'
            // ]);
        } else {
            return redirect()->back()->with('error','Can not assign provider. please try gain later or contact administrator.');

        }
    }    

    public function startBidding(Request $request) 
    {
        $request->validate([
            "booking_id"    => "required",
            "max_bid"       => "required",
            "min_bid"       => "required"
        ]);
        
        $booking_id = $request->booking_id;
        $partner_bid = PartnerBidList::where('booking_id', $booking_id)->first();

        if($partner_bid == null) {
            $partner_bid = new PartnerBidList();
        }

        $mytime                         = Carbon::now();
        $partner_bid->booking_id        = $booking_id;
        $partner_bid->max_bid           = $request->max_bid;
        $partner_bid->min_bid           = $request->min_bid;
        $partner_bid->status            = 1;
        $partner_bid->start_date_time   = $mytime->toDateTimeString();
        $partner_bid->end_date_time     = null;

        if($booking_id != null) {
            $gbst = SingleTripBooking::where('booking_id', $booking_id)->first();
            if($gbst != null) {
                $gbst->status = 'Bidding Ongoing';
                if($gbst->save()) {
                    $partner_bid->save();
                    return redirect()->route('carbook')->with('success','Partner Biding has been started successfully!');
                } else {
                    return redirect()->route('carbook')->with('error','Unable to start partner bid!');
                }
            } else {
                return redirect()->route('carbook')->with('error','Booking ID does not match to our record!');
            }
        }
    }

    public function cancelBidding($id) 
    {        
        if(strlen($id) < 15){
            $id='#'.$id;
        }else{
            $booking_id = Crypt::decryptString($id);
        }

        $gbst = SingleTripBooking::where('booking_id', $booking_id)->first();
        $gbst->status = "Pending";
        
        if($gbst->save()) 
        {
            $partner_bid = PartnerBidList::where('booking_id', $booking_id)->first();

            if($partner_bid != null) {
                $partner_bid->status = 0; //cancel bidding
                $mytime = Carbon::now();
                $partner_bid->end_date_time = $mytime->toDateTimeString();
                
                if($partner_bid->save()) {

                    $bids = Bid::where('bid_id', $partner_bid->id)->get();

                    if(sizeof($bids) != 0) {
                        Bid::where('bid_id', $partner_bid->id)->delete();
                    }
                    return redirect()->route('carbook')->with('success','Partner Biding has been canceled!');
                } else {
                    return redirect()->route('carbook')->with('error','Unable to cancel partner bidding!');
                }
            } else {
                return redirect()->route('carbook')->with('error','Unable to cancel bidding!');
            }
        } else {
            return redirect()->route('carbook')->with('error','Unable to cancel partner bidding! Booking ID not found');
        }
    }

    public function liveBids($id) 
    {
        if(strlen($id) < 15){
            $id='#'.$id;
        }else{
            $booking_id = Crypt::decryptString($id);
        }

        $gbst = SingleTripBooking::where('booking_id', $booking_id)->first();
        $live_bids = PartnerBidList::where('booking_id', $booking_id)->first();
        $bidders = Bid::where('bid_id', $live_bids->id)->latest()->get();
        return view('carbook.liveBids', compact('bidders', 'gbst'));
    }

    public function acceptBidder($id) 
    {
        // bid accepted by admin
        $bid = Bid::findOrFail($id);
        $bid->status = 2; 
        $bid->save();

        // reject other Bids
        $rejectOtherBids = Bid::where('bid_id', $bid->bid_id)->where('id', '!=', $id)->get();
        foreach($rejectOtherBids as $row) {
            $row->status = 3;
            $row->save();
        }

        // partner bid list
        $assignedBidder                     = PartnerBidList::findOrFail($bid->bid_id);
        $assignedBidder->assigned_to        = $bid->partner_id;
        $assignedBidder->status             = 2;
        $mytime                             = Carbon::now();
        $assignedBidder->end_date_time      = $mytime->toDateTimeString();

        // single Trip booking status update
        $gbst = SingleTripBooking::where('booking_id', $assignedBidder->booking_id)->first();
        $gbst->status = "Accepted Bid";
        $gbst->save();
        
        if($assignedBidder->save()) {
            return redirect()->route('carbook')->with('success', 'Bidder has been accepted successfully!');
        } else {
            return redirect()->back()->with('error', 'Something went wrong!');
        }
    }

    public function applyCoupon(Request $request) {
        $coupon_code = $request->coupon_code;
        $coupon = Coupon::where('coupon_code', $coupon_code)->first();

        if($coupon == null) {
            return 0;
        } elseif($coupon->flag == 1 && $coupon->status == 1) {
            return response()->json(['amount' => $coupon->amount, 'status' => 1]);
        } else {
            return -1;
       }
    }
}
