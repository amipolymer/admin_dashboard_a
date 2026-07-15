<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Offer Letter</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">

    <p>Hi <strong>{{ $offer['candidate_name'] ?? $employee->emp_name }}</strong>,</p>

    <p>Greetings for the Day!</p>
    <p>Congratulations!</p>
    <p>
    We are delighted to offer you a position as <strong>{{ $offer['role'] ?? '' }}</strong> ({{ $offer['designation'] ?? '' }}) at
        <strong>{{ config('app.name', 'Ami Polymer') }} - {{ $offer['location'] ?? '' }}</strong> location.
    </p>
     
    <p>We trust that your experience and skills will be a valuable asset to our company.</p>
 
    <!-- <p>Please find attached a copy of the Offer Letter. Kindly revert to this Email with your Offer Acceptance.</p> -->

    <p><strong>Terms and Conditions - Employment Agreement & Non-Disclosure Clauses:</strong></p>

    <p>Your employment with Ami Polymer Pvt. Ltd. will be subject to our standard employment agreement, including adherence to company policies and procedures. In addition, please note the non-disclosure clauses that remain valid during your employment and for three years after service cessation.
</p>
<ul style="list-style-type: disc; padding-left: 25px;">
    <li>You are restricted from engaging in similar business ventures involving products or customers closely aligned with Ami Polymer Pvt. Ltd.</li>
    <li>You may not work as an employee, consultant, or liaison for competing businesses sharing our customer base.</li>
    <li><strong>You must not share any Ami Polymer Pvt. Ltd. confidential details, such as Product Info, Technical Specs, Design, Processes, Pricing, Customer Data, Business Plans, etc. Avoid activities detrimental to our business interests.</strong></li>
</ul>
   
<p>Breach of these clauses may lead to legal actions, including civil, criminal, or both, as deemed appropriate by the company. We are enthusiastic about the potential you bring to our organization and look forward to your positive response.</p>


<p>Please feel free to reach out to me in case you need any support. We look forward to you joining us and making us bigger and better than ever.</p>
 
 <p>On your date of joining, please carry your Offer Acceptance, passport size photo & Resignation Acceptance from past employer(if applicable).</p>

<h5>Note:</h5>

<ul style="list-style-type: disc; padding-left: 25px;">
<li>Validity of this offer letter is 48 hours from the time this mail has been delivered.</li>
<li>Joining date once communicated is non-negotiable; any extension thereafter will be subject to permission of Ami Polymer Pvt. Ltd. management.</li>
<li>In case if you don’t join on the said date, Ami Polymer Pvt. Ltd. hold the right to withdraw this offer without any notice.</li>
<li>Acknowledgement to the mail will be considered as your acceptance to the clauses as mentioned in the Offer Letter.</li>
<li>We shall conduct a background verification (Educational, Employment) and your employment would be subject to the verification report. Our third party verification partner would contact you for the same (SMS & Email).</li>
</ul>

    <p>
        Please review and accept your offer letter using your onboarding portal:
        <br>
        <a href="{{ $portalLink }}" style="color:#0d6efd;">{{ $portalLink }}</a>
    </p>

    <p>
        You can also open the offer letter directly:
        <br>
        <a href="{{ $offerLink }}" style="color:#0d6efd;">View Offer Letter</a>
    </p>

    <p>Regards,<br>HR Team<br>{{ config('app.name', 'Ami Polymer') }}</p>

</body>
</html>
