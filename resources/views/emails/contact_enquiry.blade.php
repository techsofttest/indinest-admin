<x-mail::message>
# New Contact Enquiry Received

You have received a new contact enquiry from the storefront.

**Name:** {{ $data['name'] }}  
**Email:** {{ $data['email'] }}  
**Phone:** {{ $data['phone'] ?? 'N/A' }}  
**Nature of Enquiry:** {{ $data['type'] }}  

**Message Details:**  
{{ $data['message'] }}  

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
