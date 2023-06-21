@extends('layouts.app')

@section('content')
<div class=" ml-15">
    <h1>Edit Contract</h1>
</div>
<br>
<form action="{{ route('projectcontract.update')}}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="container">
        <div class="card">
            <div class="card-body">
                <div class="form-row mb-4">
                    <div class="col-md-5">
                        <input type="hidden" id="id" name="id" value={{$contracts['id']}}>
                        <input type="hidden" id="rid" name="rid" value={{$reviewer['id']}}>
                        <div class="form-group">
                            <label for="client_name" class="field-required">Client Name</label>
                            <input type="text" class="form-control" name="client_name" id="client_name"
                            placeholder="Enter Client Name" value="{{$contracts['contract_name']}}">
                            <span class="text-danger">
                                @error('client_name')
                                {{$message}}
                                @enderror
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div><br>
    </div>
    <div class="container">
        <div class="card">
            <div class="card-body">
                <div class="form-row mb-4">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label for="contract_name" class="field-required">Contract Name</label>
                            <input type="text" class="form-control" name="contract_name" id="contract_name"
                            placeholder="Enter Contract Name" value="{{$contractsmeta[0]['value']}}">
                            <span class="text-danger">
                                @error('contract_name')
                                {{$message}}
                                @enderror
                            </span>
                        </div>
                        <div class="form-group">
                            <label for="contract_date_for_effective" class="field-required">Contract Date for Effective</label>
                            <input type="date" class="form-control" name="contract_date_for_effective" id="contract_date_for_effective" value={{$contractsmeta[1]['value']}}>
                            <span class="text-danger">
                                @error('contract_date_for_effective')
                                {{$message}}
                                @enderror
                            </span>
                        </div>
                    </div>
                    <div class="col-md-5 offset-md-1">
                        <div class="form-group">
                            <label for="contract_date_for_signing" class="field-required">Contract Date for signing</label>
                            <input type="date" class="form-control" name="contract_date_for_signing" id="contract_date_for_signing" value={{$contractsmeta[2]['value']}}>
                            <span class="text-danger">
                                @error('contract_date_for_signing')
                                {{$message}}
                                @enderror
                            </span>
                        </div>
                        <div class="form-group">
                            <label for="contract_expiry_date" class="field-required">Contract Expiry Date</label>
                            <input type="date" class="form-control" name="contract_expiry_date" id="contract_expiry_date" value={{$contractsmeta[3]['value']}}>
                            <span class="text-danger">
                                @error('contract_expiry_date')
                                {{$message}}
                                @enderror
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div><br>
    </div>
    <div class="container">
            <div class="card">
                <div class="card-body">
                    <div class="form-row mb-4">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="client_name" class="field-required">Comments</label>
                                <textarea type="text" class="form-control" name="comment" id="comment"
                                placeholder="Enter Comment" rows="5" cols="50" required></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <br>
        </div>
        <div class="container">
            <div class="form-row">
                <div class="form-group col-md-12">
                    <button type="submit" class="btn btn-success round-submit"><i class="fa fa-check mr-1" ></i>Update & Approve</button>
                </div>
            </div>
        </div>
</form>
@endsection
