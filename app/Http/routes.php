<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It"s a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/


use App\Contact;
use App\ExtraJob;
use App\JobSchedule;
use App\Payment;


Route::group(['middlewareGroups' => ['web']], function () {
   Auth::loginUsingId(1);

    Route::get("/", function () {
        return view("home");
    });

    Route::get('/api/contact', function(){


        $contacts = Contact::all();
        return  Response::json([
            'totalRecord' => Contact::count(),
            'data' => $contacts
        ]);


    });


    Route::get('/api/contact/callback', function(){
        if(Request::ajax()) {
            $term = Request::input('id');
            //return $term;
            $contact = Contact::where('id',$term)->get();
            //return $contact;
            return \Response::json($contact);
        }
    });

    //Populate Records Event
    Route::get("/api/calendarEventsLoad", function () {
        $jobschedules = JobSchedule::with('sitecontacts','payments')->get();
        //return $jobschedules;
        foreach($jobschedules as $jobschedule) {
            $events[] = array(
                'job_schedule_id' => $jobschedule->id,
                'contact_id' => $jobschedule->contact_id,
                'title' => $jobschedule->title,
                'job_assign_color' => $jobschedule->job_assign_color,
                'pre_job_sched_status' => $jobschedule->pre_job_sched_status,
                'job_sched_status' => $jobschedule->job_sched_status,
                'service_type' => $jobschedule->service_type,
                'est_job_time_sched' => $jobschedule->est_job_time_sched,
                'technician' => $jobschedule->technician,
                'start' => Carbon::createFromFormat('Y-m-d H:i:s', $jobschedule->start)->toDateTimeString(),
                'end'   => Carbon::createFromFormat('Y-m-d H:i:s', $jobschedule->end)->toDateTimeString(),
                'job_description'    => $jobschedule->job_description,

                'job_site_address'    => $jobschedule->job_site_address,
                'job_site_suburb'    => $jobschedule->job_site_suburb,
                'job_site_postal_code'    => $jobschedule->job_site_postal_code,
                'job_site_country'    => $jobschedule->job_site_country,

                'payment_estimated_charge'    => $jobschedule->payments->payment_estimated_charge,
                'payment_actual_charge'    => $jobschedule->payments->payment_actual_charge,

                'site_contact_name' => $jobschedule->sitecontacts->site_contact_name,
                //'site_contact_job_title' => $jobschedule->sitecontacts->site_contact_job_title,
                'site_contact_phone1' => $jobschedule->sitecontacts->site_contact_phone1,
                'site_contact_phone2' => $jobschedule->sitecontacts->site_contact_phone2,
                'site_contact_notes' => $jobschedule->sitecontacts->site_contact_notes,
            );
        }
        //return $events;
        return json_encode($events);

    });

    //Add Events
    Route::post('/api/addEvent', function(){
        //return Request::all();

        if(Request::ajax()) {

            $contact = Contact::find(Request::input('contact'));
            $contact->jobschedules()->create([
                'job_order_number' => Request::input('job_order_number'),
                'job_queue' => Request::input('job_queue'),
                'job_assign_color' => Request::input('setcolor'),
                'service_type' => Request::input('service_type'),
                'technician' => Request::input('technician'),
                'job_description' => Request::input('job_description'),
                'job_notes' => Request::input('job_notes'),
                'pre_job_sched_status' => Request::input('pre_job_sched_status'),
                'job_sched_status' => Request::input('job_sched_status'),
                'est_job_time_sched' => Request::input('est_job_time_sched'),
                'job_site_address' => Request::input('job_site_address'),
                'job_site_suburb' => Request::input('job_site_suburb'),
                'job_site_postal_code' => Request::input('job_site_postal_code'),
                'job_site_country' => Request::input('job_site_country'),
                'title' => $contact->name,
                'start' => date('Y-m-d H:i:s',strtotime(Request::input('start_date'))),
                'end' => date('Y-m-d H:i:s',strtotime(Request::input('end_date'))),
            ]);

            $jobsched = JobSchedule::find($contact->jobschedules->last()->id);
            $jobsched->extrajobs()->create([
                'extra_service_type' => Request::input('extra_service_type'),
                'extra_job_description' => Request::input('extra_job_description'),
                'extra_job_assign_tech' => Request::input('extra_job_assign_tech'),
                'extra_payment_type' => Request::input('extra_payment_type'),
                'extra_payment_status' => Request::input('extra_payment_status'),
                'extra_job_total_payment' => Request::input('extra_job_total_payment')
            ]);
            $jobsched->servicecalls()->create([
                'sc_service_type' => Request::input('sc_service_type'),
                'sc_job_description' => Request::input('sc_job_description'),
                'sc_job_fault_tech' => Request::input('sc_job_fault_tech'),
                'sc_job_assign_tech' => Request::input('sc_job_assign_tech'),
                'sc_est_service_charge' => Request::input('sc_est_service_charge'),
            ]);
            $jobsched->payments()->create([
                'payment_type' => Request::input('payment_type'),
                'payment_status' => Request::input('payment_status'),
                'payment_estimated_charge' => Request::input('payment_estimated_charge'),
                'payment_actual_charge' => Request::input('payment_actual_charge'),
                'payment_initial_deposit' => Request::input('payment_initial_deposit'),
                'payment_collectible_amount' => Request::input('payment_collectible_amount'),
                'payment_description' => Request::input('payment_description'),
            ]);
            $jobsched->sitecontacts()->create([
                'site_contact_name' => Request::input('site_contact_name'),
                'site_contact_job_title' => Request::input('site_contact_job_title'),
                'site_contact_phone1' => Request::input('site_contact_phone1'),
                'site_contact_phone2' => Request::input('site_contact_phone2'),
                'site_contact_notes' => Request::input('site_contact_notes'),
            ]);
            return Response::json(
                [
                    'response' => 'AddEvent',
                    'data' => $contact->jobschedules->last()
                ]
            );
        }
    });

    //Edit Events
    Route::get('/api/editEvent/{id}/edit/', function($id){
        $jobschedules = JobSchedule::find($id);
        //return  $contact;
        return Response::json(
            [
                'response' => 'EditEvent',
                'data' => $jobschedules->load('payments','sitecontacts')
            ]
        );
    });

});