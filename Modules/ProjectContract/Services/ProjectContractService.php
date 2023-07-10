<?php

namespace Modules\ProjectContract\Services;

use Illuminate\Support\Facades\Auth;
use Modules\ProjectContract\Entities\Contract;
use Modules\ProjectContract\Entities\ProjectContractMeta;
use Modules\ProjectContract\Http\Requests\ProjectContractRequest;
use Modules\ProjectContract\Entities\Reviewer;
use Modules\ProjectContract\Entities\ContractReview;
use Modules\ProjectContract\Entities\ContractInternalReview;
use Modules\ProjectContract\Entities\ContractMetaHistory;
use Modules\User\Entities\User;
use Illuminate\Support\Facades\DB;

class ProjectContractService
{
    public function index()
    {
        $user = Auth::user();

        $userContracts = Contract::where('user_id', $user->id)
            ->with('internalReviewers.user', 'contractReviewers')
            ->get();

        $reviewerContracts = Contract::whereHas('internalReviewers', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->with(['user', 'internalReviewers' => function ($query) {
            $query->whereIn('user_type', ['cc-team', 'finance-team']);
        }, 'contractReviewers'])
        ->get();

        $contracts = $userContracts->merge($reviewerContracts);

        return $contracts;
    }

    public function internal_reviewer()
    {
        return Contract::join('contract_internal_reviewer', 'contracts.id', '=', 'contract_internal_reviewer.contract_id')->where('contract_internal_reviewer.user_id', Auth::id())->get();
    }
    public function store($request)
    {
        $contractData = [
            'user_id' => Auth::id(),
            'contract_name' => $request['client_name'],
            'status' => 'Saved as draft',
        ];
        if (isset($request['gst'])) {
            $gst = $request['gst'];
        } else {
            $gst = 'N/A';
        }
        $contractMeta = [
            ['key' => 'Contract Name', 'value' => $request['contract_name'], 'group' => 'Contract Details'],
            ['key' => 'Contract Date For Effective', 'value' => $request['contract_date_for_effective'], 'group' => 'Contract Details'],
            ['key' => 'Contract Date For Signing', 'value' => $request['contract_date_for_signing'], 'group' => 'Contract Details'],
            ['key' => 'Contract Date For Expiry', 'value' => $request['contract_expiry_date'], 'group' => 'Contract Details'],
            ['key' => 'Authority Name', 'value' => $request['authority_name'], 'group' => 'Signing Authority'],
            ['key' => 'Phone number', 'value' => $request['phonenumber'], 'group' => 'Signing Authority'],
            ['key' => 'Authority Designation', 'value' => $request['designation'], 'group' => 'Signing Authority'],
            ['key' => 'Authority Email', 'value' => $request['email'], 'group' => 'Signing Authority'],
            ['key' => 'Project Summary', 'value' => $request['summary'], 'group' => 'Project Summary'],
            ['key' => 'Project Cost', 'value' => $request['cost'], 'group' => 'Project Cost'],
            ['key' => 'Payment Currency', 'value' => $request['currency'], 'group' => 'Payment Methodology'],
            ['key' => 'Payment methodology', 'value' => $request['methodology'], 'group' => 'Payment Methodology'],
            ['key' => 'Source of Payment', 'value' => $request['source'], 'group' => 'Payment Methodology'],
            ['key' => 'GST Number', 'value' => $gst, 'group' => 'Payment Methodology'],
        ];

        $contractId = null;

        DB::transaction(function () use ($contractData, $contractMeta, &$contractId) {
            $contract = Contract::create($contractData);

            foreach ($contractMeta as $meta) {
                $contract->contractMeta()->create($meta);
            }

            $contractId = $contract->id;
        });

        return $contractId;
    }

    public function delete($id)
    {
        return ProjectContractMeta::find($id)->delete();
    }

    public function update(ProjectContractRequest $request, $id)
    {
        if ($request->hasFile('logo_img')) {
            $file = $request->file('logo_img');
            $path = 'app/public/contractlogo';
            $imageName = $file->getClientOriginalName();
            $fullpath = $file->move(storage_path($path), $imageName);
        }
        $validated = $request->validated();
        $ProjectContractMeta = ProjectContractMeta::find($id);

        $ProjectContractMeta->client_id = $request->get('client_id');
        $ProjectContractMeta->website_url = $validated['website_url'];
        $ProjectContractMeta->logo_img = $validated['logo_img'];
        $ProjectContractMeta->authority_name = $validated['authority_name'];
        $ProjectContractMeta->contract_date_for_signing = $validated['contract_date_for_signing'];
        $ProjectContractMeta->contract_date_for_effective = $validated['contract_date_for_effective'];
        $ProjectContractMeta->contract_expiry_date = $validated['contract_expiry_date'];
        $ProjectContractMeta->attributes = json_encode($request['attributes']);
        $ProjectContractMeta->save();

        return $ProjectContractMeta;
    }
    public function viewContract($id)
    {
        return Contract::find($id);
    }
    public function viewContractMeta($id)
    {
        return Contract::find($id)->contractMeta()->get();
    }
    public function viewContractMetaGroup($id)
    {
        return Contract::find($id)->contractMeta()->get()->groupBy('group');
    }
    public function viewReviewer($id, $email)
    {
        return Reviewer::where(['contract_id'=>$id, 'email'=>$email])->first();
    }
    public function viewInternalReviewer($id)
    {
        return ContractInternalReview::where('contract_id', $id)->first();
    }
    public function viewComments($id)
    {
        if (ContractReview::find($id)) {
            return ContractReview::where('contract_id', '=', $id)->orderBy('created_at', 'desc')->get();
        }
    }
    public function updateContract($id)
    {
        $contract = Contract::find($id);
        $contract->status = 'finalise-by-client';
        $contract->save();
        $reviewer = Reviewer::where(['contract_id' => $id])->first();
        $reviewer->status = 'approved';
        $reviewer->save();

        return $contract;
    }
    public function updateInternalContract($id)
    {
        $contract = Contract::find($id);
        if ($contract->user_id == Auth::id()) {
            $reviewer = ContractInternalReview::where(['contract_id' => $id, 'user_type' => 'cc-team'])->first();
            $reviewer->status = 'approved';
            $reviewer->save();
            $contract->status = 'finalise-by-User';
        } else {
            $reviewer = ContractInternalReview::where(['contract_id' => $id, 'user_type' => 'finance-team'])->first();
            $reviewer->status = 'approved';
            $reviewer->save();
            $contract->status = 'finalise-by-finance';
        }
        $contract->save();

        return $contract;
    }
    public function storeReveiwer($request)
    {
        $id = $request['id'];
        $name = $request['name'];
        $email = $request['email'];

        $reviewer = new Reviewer;
        $reviewer->contract_id = $id;
        $reviewer->name = $name;
        $reviewer->email = $email;
        $reviewer->status = 'pending';
        $reviewer->save();

        $Contract = Contract::find($id);
        $Contract->status = 'sent-for-client-review';
        $Contract->save();

        return $reviewer;
    }
    public function editContract($request)
    {
        $contractId = $request['id'];

        $contractReview = new ContractReview();
        $contractReview->contract_id = $contractId;
        $contractReview->comment = $request['comment'];

        $id = Reviewer::find($request['rid']);
        $contractReview->comment()->associate($id);
        $contractReview->save();

        $contractData = [
            'contract_name' => $request['client_name'],
            'status' => 'updated-by-client',
        ];
        if (isset($request['gst'])) {
            $gst = $request['gst'];
        } else {
            $gst = 'N/A';
        }
        $contractMeta = [
            ['key' => 'Contract Name', 'value' => $request['contract_name']],
            ['key' => 'Contract Date For Effective', 'value' => $request['contract_date_for_effective']],
            ['key' => 'Contract Date For Signing', 'value' => $request['contract_date_for_signing']],
            ['key' => 'Contract Date For Expiry', 'value' => $request['contract_expiry_date']],
            ['key' => 'Authority Name', 'value' => $request['authority_name']],
            ['key' => 'Phone number', 'value' => $request['phonenumber']],
            ['key' => 'Authority Designation', 'value' => $request['designation']],
            ['key' => 'Authority Email', 'value' => $request['email']],
            ['key' => 'Project Summary', 'value' => $request['summary']],
            ['key' => 'Project Cost', 'value' => $request['cost']],
            ['key' => 'Payment Currency', 'value' => $request['currency']],
            ['key' => 'Payment methodology', 'value' => $request['methodology']],
            ['key' => 'Source of Payment', 'value' => $request['source']],
            ['key' => 'GST Number', 'value' => $gst],
        ];

        DB::transaction(function () use ($contractId, $contractData, $contractMeta, $contractReview) {
            $contract = Contract::where('id', $contractId)->first();
            $contract->update($contractData);
            $existingMeta = $contract->contractMeta()->where('contract_id', $contractId)->get();

            foreach ($contractMeta as $meta) {
                $contract->contractMeta()->updateOrCreate(['key' => $meta['key']], ['value' => $meta['value']]);
                foreach ($existingMeta as $emeta) {
                    if ($emeta->key == $meta['key'] and $emeta->value != $meta['value']) {
                        $contract->contractMetaHistory()->create([
                            'contract_id' => $contract->id,
                            'key' => $meta['key'],
                            'value' => $emeta['value'],
                            'review_id' => $contractReview->id,
                            'has_changed' => true

                        ]);
                    } elseif ($emeta->key == $meta['key'] and $emeta->value == $meta['value']) {
                        $contract->contractMetaHistory()->create([
                            'contract_id' => $contract->id,
                            'key' => $meta['key'],
                            'value' => $emeta['value'],
                            'review_id' => $contractReview->id,
                        ]);
                    }
                }
            }
        });

        return $contractData;
    }

    public function storeInternalReveiwer($request)
    {
        $id = $request['id'];
        $email = $request['email'];

        $Reviewer = new ContractInternalReview;
        $Reviewer->contract_id = $id;
        $Reviewer->email = $email;
        $User = User::findByEmail($email);
        $Reviewer->user_id = $User->id;
        $Reviewer->name = $User->name;
        $Reviewer->status = 'pending';
        $Reviewer->user_type = 'finance-team';
        $Reviewer->save();

        $Contract = Contract::find($id);
        $Contract->status = 'sent-for-finance-review';
        $Contract->save();

        return $Reviewer;
    }
    public function updateInternal($request)
    {
        $contractReview = new ContractReview();
        $contractReview->contract_id = $request['id'];
        $contractReview->comment = $request['comment'];
        $id = ContractInternalReview::find($request['rid']);
        $contractReview->comment()->associate($id);
        $contractReview->save();

        $contractData = [
            'contract_name' => $request['client_name'],
            'status' => 'updated-by-finance',
        ];

        $id = Contract::where('id', $request['id'])->first();
        if ($id->user_id == Auth::id()) {
            $contractData['status'] = 'updated-by-cc-team';
        }
        if (isset($request['gst'])) {
            $gst = $request['gst'];
        } else {
            $gst = 'N/A';
        }
        $contractMeta = [
            ['key' => 'Contract Name', 'value' => $request['contract_name']],
            ['key' => 'Contract Date For Effective', 'value' => $request['contract_date_for_effective']],
            ['key' => 'Contract Date For Signing', 'value' => $request['contract_date_for_signing']],
            ['key' => 'Contract Date For Expiry', 'value' => $request['contract_expiry_date']],
            ['key' => 'Authority Name', 'value' => $request['authority_name']],
            ['key' => 'Phone number', 'value' => $request['phonenumber']],
            ['key' => 'Authority Designation', 'value' => $request['designation']],
            ['key' => 'Authority Email', 'value' => $request['email']],
            ['key' => 'Project Summary', 'value' => $request['summary']],
            ['key' => 'Project Cost', 'value' => $request['cost']],
            ['key' => 'Payment Currency', 'value' => $request['currency']],
            ['key' => 'Payment methodology', 'value' => $request['methodology']],
            ['key' => 'Source of Payment', 'value' => $request['source']],
            ['key' => 'GST Number', 'value' => $gst],
        ];

        DB::transaction(function () use ($request, $contractData, $contractMeta, $contractReview) {
            $contract = Contract::where('id', $request['id'])->first();
            $contract->update($contractData);
            $existingMeta = $contract->contractMeta()->where('contract_id', $request['id'])->get();

            foreach ($contractMeta as $meta) {
                $contract->contractMeta()->updateOrCreate(['key' => $meta['key']], ['value' => $meta['value']]);
                foreach ($existingMeta as $emeta) {
                    if ($emeta->key == $meta['key'] and $emeta->value != $meta['value']) {
                        $contract->contractMetaHistory()->create([
                            'contract_id' => $contract->id,
                            'key' => $meta['key'],
                            'value' => $emeta['value'],
                            'review_id' => $contractReview->id,
                            'has_changed' => true
                        ]);
                    } elseif ($emeta->key == $meta['key'] and $emeta->value == $meta['value']) {
                        $contract->contractMetaHistory()->create([
                            'contract_id' => $contract->id,
                            'key' => $meta['key'],
                            'value' => $emeta['value'],
                            'review_id' => $contractReview->id,
                        ]);
                    }
                }
            }
        });

        $this->updateInternalContract($request['id']);

        return $contractData;
    }
    public function storeUser($id)
    {
        $reviewer = new ContractInternalReview;
        $reviewer->contract_id = $id;
        $reviewer->name = Auth::user()->name;
        $reviewer->email = Auth::user()->email;
        $reviewer->user_id = Auth::id();
        $reviewer->status = 'pending';
        $reviewer->user_type = 'cc-team';
        $reviewer->save();

        return $reviewer;
    }
    public function getCommentHistory($id)
    {
        return ContractMetaHistory::join('contract_review', 'contract_meta_history.review_id', '=', 'contract_review.id')->where('contract_meta_history.review_id', $id)->get();
    }
    public function getUserEmail($id)
    {
        $contract = Contract::find($id);
        $user = User::where(['id' =>$contract->user_id])->first();

        return $user->email;
    }
    public function getStatus($id)
    {
        return ContractInternalReview::select('status')->where(['contract_id' => $id, 'user_id' => Auth::id()])->first();
    }
    public function getFinanceStatus($id)
    {
        return ContractInternalReview::select('status')->where(['contract_id' => $id, 'user_type' => 'finance-team'])->first();
    }
    public function getClientStatus($id)
    {
        return Reviewer::select('status')->where(['contract_id' => $id])->first();
    }
    public function getUsers()
    {
        return User::all();
    }
}
