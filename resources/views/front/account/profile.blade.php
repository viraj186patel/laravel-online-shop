@extends('front.layouts.app')

@section('content')
    <section class="section-5 pt-3 pb-3 mb-3 bg-white">
        <div class="container">
            <div class="light-font">
                <ol class="breadcrumb primary-color mb-0">
                    <li class="breadcrumb-item"><a class="white-text" href="#">My Account</a></li>
                    <li class="breadcrumb-item">Settings</li>
                </ol>
            </div>
        </div>
    </section>

    <section class=" section-11 ">
        <div class="container  mt-5">
            <div class="row">
                <div class="col-md-12">
                    @include('front.account.common.message')
                </div>
                <div class="col-md-3">
                    @include('front.account.common.sidebar')
                </div>
                <div class="col-md-9">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="h5 mb-0 pt-2 pb-2">Personal Information</h2>
                        </div>
                        <form action="" name="profileForm" id="profileForm">
                            <div class="card-body p-4">
                                <div class="row">
                                    <div class="mb-3">               
                                        <label for="name">Name</label>
                                        <input type="text" name="name" id="name" placeholder="Enter Your Name" class="form-control" value="{{ $user->name }}">
                                        <p></p>
                                    </div>
                                    <div class="mb-3">            
                                        <label for="email">Email</label>
                                        <input type="text" name="email" id="email" placeholder="Enter Your Email" class="form-control" value="{{ $user->email }}">
                                        <p></p>
                                    </div>
                                    <div class="mb-3">                                    
                                        <label for="phone">Phone</label>
                                        <input type="text" name="phone" id="phone" placeholder="Enter Your Phone" class="form-control" value="{{ $user->phone }}">
                                        <p></p>
                                    </div>
                                    <div class="d-flex">
                                        <button id="profileBtn" class="btn btn-dark">Update</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="card mt-5">
                        <div class="card-header">
                            <h2 class="h5 mb-0 pt-2 pb-2">Address</h2>
                        </div>
                        <form action="" name="addressForm" id="addressForm">
                            <div class="card-body p-4">
                                <div class="row">
                                    <div class="col-md-6 mb-3">               
                                        <label for="first_name">First Name</label>
                                        <input type="text" name="first_name" id="first_name" placeholder="Enter Your First Name" class="form-control" value="{{ (!empty($customerAddress)) ? $customerAddress->first_name : '' }}">
                                        <p></p>
                                    </div>
                                    <div class="col-md-6 mb-3">            
                                        <label for="last_name">Last Name</label>
                                        <input type="text" name="last_name" id="last_name" placeholder="Enter Your Last Name" class="form-control" value="{{ (!empty($customerAddress)) ? $customerAddress->last_name : '' }}">
                                        <p></p>
                                    </div>
                                    <div class="col-md-6 mb-3">            
                                        <label for="email">Email</label>
                                        <input type="text" name="email" id="email" placeholder="Enter Your Email" class="form-control" value="{{ (!empty($customerAddress)) ? $customerAddress->email : '' }}">
                                        <p></p>
                                    </div>
                                    <div class="col-md-6 mb-3">                                    
                                        <label for="mobile">Mobile</label>
                                        <input type="text" name="mobile" id="mobile" placeholder="Enter Your Mobile" class="form-control" value="{{ (!empty($customerAddress)) ? $customerAddress->mobile : '' }}">
                                        <p></p>
                                    </div>
                                    <div class="mb-3">                                    
                                        <label for="country">Country</label>
                                        <select name="country_id" id="country_id" class="form-control">
                                            <option value="">Select a Country</option>
                                            @if ($countries->isNotEmpty())
                                                @foreach ($countries as $country)
                                                    <option value="{{ $country->id }}" {{ (!empty($customerAddress) && $customerAddress->country_id == $country->id) ? 'selected' : '' }}>{{ $country->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <p></p>
                                    </div>
                                    <div class="mb-3">
                                        <label for="address">Address</label>
                                        <textarea name="address" id="address" cols="30" rows="5" class="form-control">{{ (!empty($customerAddress)) ? $customerAddress->address : '' }}</textarea>
                                        <p></p>
                                    </div>
                                    <div class="col-md-6 mb-3">                                    
                                        <label for="apartment">Apartment</label>
                                        <input type="text" name="apartment" id="apartment" placeholder="Enter Your Apartment" class="form-control" value="{{ (!empty($customerAddress)) ? $customerAddress->apartment : '' }}">
                                        <p></p>
                                    </div>
                                    <div class="col-md-6 mb-3">                                    
                                        <label for="city">City</label>
                                        <input type="text" name="city" id="city" placeholder="Enter Your City" class="form-control" value="{{ (!empty($customerAddress)) ? $customerAddress->city : '' }}">
                                        <p></p>
                                    </div>
                                    <div class="col-md-6 mb-3">                                    
                                        <label for="state">State</label>
                                        <input type="text" name="state" id="state" placeholder="Enter Your State" class="form-control" value="{{ (!empty($customerAddress)) ? $customerAddress->state : '' }}">
                                        <p></p>
                                    </div>
                                    <div class="col-md-6 mb-3">                                    
                                        <label for="zip">Zip</label>
                                        <input type="text" name="zip" id="zip" placeholder="Enter Your Zip" class="form-control" value="{{ (!empty($customerAddress)) ? $customerAddress->zip : '' }}">
                                        <p></p>
                                    </div>
                                    <div class="d-flex">
                                        <button id="addressBtn" class="btn btn-dark">Update</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('customJs')
    <script>
        $("#profileForm").submit(function(e){
            e.preventDefault();

            $("#profileBtn").prop("disabled",true);

            $.ajax({
                url: '{{ route("account.updateProfile") }}',
                type: 'post',
                data: $(this).serializeArray(),
                dataType: 'json',
                success: function(response){

                    $("#profileBtn").prop("disabled",false);

                    if(response.status == true){
                        window.location.href = '{{ route("account.profile") }}';

                        $('#profileForm #name').removeClass('is-invalid').siblings('p').removeClass('invalid-feedback').html('');
                        $('#profileForm #email').removeClass('is-invalid').siblings('p').removeClass('invalid-feedback').html('');
                        $('#profileForm #phone').removeClass('is-invalid').siblings('p').removeClass('invalid-feedback').html('');
                    } else {
                        var errors = response.errors;

                        if(errors.name){
                            $('#profileForm #name').addClass('is-invalid').siblings('p').addClass('invalid-feedback').html(errors.name);
                        } else {
                            $('#profileForm #name').removeClass('is-invalid').siblings('p').removeClass('invalid-feedback').html('');
                        }

                        if(errors.email){
                            $('#profileForm #email').addClass('is-invalid').siblings('p').addClass('invalid-feedback').html(errors.email);
                        } else {
                            $('#profileForm #email').removeClass('is-invalid').siblings('p').removeClass('invalid-feedback').html('');
                        }

                        if(errors.phone){
                            $('#profileForm #phone').addClass('is-invalid').siblings('p').addClass('invalid-feedback').html(errors.phone);
                        } else {
                            $('#profileForm #phone').removeClass('is-invalid').siblings('p').removeClass('invalid-feedback').html('');
                        }
                    }
                }
            });
        });

        $("#addressForm").submit(function(e){
            e.preventDefault();

            $("#addressBtn").prop("disabled",true);

            $.ajax({
                url: '{{ route("account.updateAddress") }}',
                type: 'post',
                data: $(this).serializeArray(),
                dataType: 'json',
                success: function(response){

                    $("#addressBtn").prop("disabled",false);

                    if(response.status == true){
                        window.location.href = '{{ route("account.profile") }}';

                        $('#first_name').removeClass('is-invalid').siblings('p').removeClass('invalid-feedback').html('');
                        $('#last_name').removeClass('is-invalid').siblings('p').removeClass('invalid-feedback').html('');
                        $('#addressForm #email').removeClass('is-invalid').siblings('p').removeClass('invalid-feedback').html('');
                        $('#mobile').removeClass('is-invalid').siblings('p').removeClass('invalid-feedback').html('');
                        $('#country_id').removeClass('is-invalid').siblings('p').removeClass('invalid-feedback').html('');
                        $('#address').removeClass('is-invalid').siblings('p').removeClass('invalid-feedback').html('');
                        $('#apartment').removeClass('is-invalid').siblings('p').removeClass('invalid-feedback').html('');
                        $('#city').removeClass('is-invalid').siblings('p').removeClass('invalid-feedback').html('');
                        $('#state').removeClass('is-invalid').siblings('p').removeClass('invalid-feedback').html('');
                        $('#zip').removeClass('is-invalid').siblings('p').removeClass('invalid-feedback').html('');
                    } else {
                        var errors = response.errors;

                        if(errors.first_name){
                            $('#first_name').addClass('is-invalid').siblings('p').addClass('invalid-feedback').html(errors.first_name);
                        } else {
                            $('#first_name').removeClass('is-invalid').siblings('p').removeClass('invalid-feedback').html('');
                        }

                        if(errors.last_name){
                            $('#last_name').addClass('is-invalid').siblings('p').addClass('invalid-feedback').html(errors.last_name);
                        } else {
                            $('#last_name').removeClass('is-invalid').siblings('p').removeClass('invalid-feedback').html('');
                        }

                        if(errors.email){
                            $('#addressForm #email').addClass('is-invalid').siblings('p').addClass('invalid-feedback').html(errors.email);
                        } else {
                            $('#addressForm #email').removeClass('is-invalid').siblings('p').removeClass('invalid-feedback').html('');
                        }

                        if(errors.mobile){
                            $('#mobile').addClass('is-invalid').siblings('p').addClass('invalid-feedback').html(errors.mobile);
                        } else {
                            $('#mobile').removeClass('is-invalid').siblings('p').removeClass('invalid-feedback').html('');
                        }

                        if(errors.country_id){
                            $('#country_id').addClass('is-invalid').siblings('p').addClass('invalid-feedback').html(errors.country_id);
                        } else {
                            $('#country_id').removeClass('is-invalid').siblings('p').removeClass('invalid-feedback').html('');
                        }

                        if(errors.address){
                            $('#address').addClass('is-invalid').siblings('p').addClass('invalid-feedback').html(errors.address);
                        } else {
                            $('#address').removeClass('is-invalid').siblings('p').removeClass('invalid-feedback').html('');
                        }

                        if(errors.apartment){
                            $('#apartment').addClass('is-invalid').siblings('p').addClass('invalid-feedback').html(errors.apartment);
                        } else {
                            $('#apartment').removeClass('is-invalid').siblings('p').removeClass('invalid-feedback').html('');
                        }

                        if(errors.city){
                            $('#city').addClass('is-invalid').siblings('p').addClass('invalid-feedback').html(errors.city);
                        } else {
                            $('#city').removeClass('is-invalid').siblings('p').removeClass('invalid-feedback').html('');
                        }

                        if(errors.state){
                            $('#state').addClass('is-invalid').siblings('p').addClass('invalid-feedback').html(errors.state);
                        } else {
                            $('#state').removeClass('is-invalid').siblings('p').removeClass('invalid-feedback').html('');
                        }

                        if(errors.zip){
                            $('#zip').addClass('is-invalid').siblings('p').addClass('invalid-feedback').html(errors.zip);
                        } else {
                            $('#zip').removeClass('is-invalid').siblings('p').removeClass('invalid-feedback').html('');
                        }
                    }
                }
            });
        });
    </script>
@endsection