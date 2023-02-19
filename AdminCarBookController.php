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
