<?php

/**
 * This file is a part of horstoeko/orderx.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace horstoeko\orderx;

use DateTime;

/**
 * Class representing the document builder for outgoing documents
 *
 * @category Order-X
 * @package  Order-X
 * @author   D. Erling <horstoeko@erling.com.de>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     https://github.com/horstoeko/orderx
 */
class OrderDocumentBuilder extends OrderDocument
{
    /**
     * HeaderTradeAgreement
     *
     * @var object
     */
    protected $headerTradeAgreement = null;

    /**
     * HeaderTradeDelivery
     *
     * @var object
     */
    protected $headerTradeDelivery = null;

    /**
     * HeaderTradeSettlement
     *
     * @var object
     */
    protected $headerTradeSettlement = null;

    /**
     * SupplyChainTradeTransactionType
     *
     * @var object
     */
    protected $headerSupplyChainTradeTransaction = null;

    /**
     * Last added payment terms
     *
     * @var object
     */
    protected $currentPaymentTerms = null;

    /**
     * Last added position (line) to the docuemnt
     *
     * @var object
     */
    protected $currentPosition = null;

    /**
     * Constructor
     *
     * @codeCoverageIgnore
     * @param int $profile
     */
    public function __construct(int $profile)
    {
        parent::__construct($profile);

        $this->initNewDocument();
    }

    /**
     * Creates a new OrderDocumentBuilder with profile $profile
     *
     * @codeCoverageIgnore
     *
     * @param integer $profile
     * @return OrderDocumentBuilder
     */
    public static function createNew(int $profile): OrderDocumentBuilder
    {
        return (new self($profile));
    }

    /**
     * Initialized a new document with profile settings
     *
     * @return OrderDocumentBuilder
     */
    public function initNewDocument(): OrderDocumentBuilder
    {
        $this->invoiceObject = $this->objectHelper->getCrossIndustryInvoice();
        $this->headerTradeAgreement = $this->invoiceObject->getSupplyChainTradeTransaction()->getApplicableHeaderTradeAgreement();
        $this->headerTradeDelivery = $this->invoiceObject->getSupplyChainTradeTransaction()->getApplicableHeaderTradeDelivery();
        $this->headerTradeSettlement = $this->invoiceObject->getSupplyChainTradeTransaction()->getApplicableHeaderTradeSettlement();
        $this->headerSupplyChainTradeTransaction = $this->invoiceObject->getSupplyChainTradeTransaction();

        return $this;
    }

    /**
     * This method can be overridden in derived class
     * It is called before a XML is written
     *
     * @return void
     */
    protected function onBeforeGetContent()
    {
        // Do nothing
    }

    /**
     * Write the content of a Oder object to a string
     *
     * @return string
     */
    public function getContent(): string
    {
        $this->onBeforeGetContent();
        return $this->serializer->serialize($this->invoiceObject, 'xml');
    }

    /**
     * Write the content of a Order object to a file
     *
     * @param string $xmlfilename
     * @return OrderDocument
     */
    public function writeFile(string $xmlfilename): OrderDocument
    {
        file_put_contents($xmlfilename, $this->getContent());
        return $this;
    }

    /**
     * Set main information about this document
     *
     * @param string $documentNo
     * The document no issued by the seller
     * @param string $documentTypeCode
     * The type of the document, See \horstoeko\codelists\OrderInvoiceType for details
     * @param DateTime $documentDate Date of order
     * The date when the document was issued by the seller
     * @param string $orderCurrency Code for the order currency
     * The code for the order currency
     * @param string|null $documentName Document Type
     * The document type (free text)
     * @param string|null $documentLanguage Language indicator
     * The language code in which the document was written
     * @param DateTime|null $effectiveSpecifiedPeriod
     * The contractual due date of the order
     * @param string|null $purposeCode The purpose, expressed as text,
     * of this exchanged document.
     * -  7 : Duplicate
     * -  9 : Original
     * - 35 : Retransmission
     * @param string|null $requestedResponseTypeCode A code specifying a type of
     * response requested for this exchanged document. Value = AC to request an Order_Response
     * @return OrderDocumentBuilder
     */
    public function setDocumentInformation(string $documentNo, string $documentTypeCode, DateTime $documentDate, string $orderCurrency, ?string $documentName = null, ?string $documentLanguage = null, ?DateTime $effectiveSpecifiedPeriod = null, ?string $purposeCode = null, ?string $requestedResponseTypeCode = null): OrderDocumentBuilder
    {
        $this->objectHelper->tryCall($this->invoiceObject->getExchangedDocument(), "setID", $this->objectHelper->getIdType($documentNo));
        $this->objectHelper->tryCall($this->invoiceObject->getExchangedDocument(), "setName", $this->objectHelper->getTextType($documentName));
        $this->objectHelper->tryCall($this->invoiceObject->getExchangedDocument(), "setTypeCode", $this->objectHelper->getCodeType($documentTypeCode));
        $this->objectHelper->tryCall($this->invoiceObject->getExchangedDocument(), "setIssueDateTime", $this->objectHelper->getDateTimeType($documentDate));
        $this->objectHelper->tryCall($this->invoiceObject->getExchangedDocument(), "addToLanguageID", $this->objectHelper->getIdType($documentLanguage));
        $this->objectHelper->tryCall($this->invoiceObject->getExchangedDocument(), "setEffectiveSpecifiedPeriod", $this->objectHelper->getSpecifiedPeriodType($effectiveSpecifiedPeriod, $effectiveSpecifiedPeriod));
        $this->objectHelper->tryCall($this->invoiceObject->getExchangedDocument(), "setPurposeCode", $this->objectHelper->getCodeType($purposeCode));
        $this->objectHelper->tryCall($this->invoiceObject->getExchangedDocument(), "setRequestedResponseTypeCode", $this->objectHelper->getCodeType($requestedResponseTypeCode));

        $this->objectHelper->tryCall($this->headerTradeSettlement, "setOrderCurrencyCode", $this->objectHelper->getIdType($orderCurrency));

        return $this;
    }

    /**
     * Set the documents business process specified document ontext parameter
     *
     * @param string $businessProcessSpecifiedDocumentContextParameter
     * Identifies the business process context in which the transaction appears,
     * to enable the Buyer to process the Order in an appropriate way.
     *
     * @return OrderDocumentBuilder
     */
    public function setDocumentBusinessProcessSpecifiedDocumentContextParameter(string $businessProcessSpecifiedDocumentContextParameter): OrderDocumentBuilder
    {
        $this->objectHelper->tryCall($this->invoiceObject->getExchangedDocumentContext(), "setBusinessProcessSpecifiedDocumentContextParameter", $this->objectHelper->getDocumentContextParameterType($businessProcessSpecifiedDocumentContextParameter));

        return $this;
    }

    /**
     * Mark document as a copy from the original one
     *
     * @param boolean|null $isDocumentCopy
     * Is document a copy. If this parameter is not submitted the true is suggested
     * @return OrderDocumentBuilder
     */
    public function setIsDocumentCopy(?bool $isDocumentCopy = null): OrderDocumentBuilder
    {
        $this->objectHelper->tryCall($this->invoiceObject->getExchangedDocument(), "setCopyIndicator", $this->objectHelper->getIndicatorType($isDocumentCopy ?? true));
        return $this;
    }

    /**
     * Mark document as a test document
     *
     * @param boolean|null $isTestDocument
     * Is document a test. If this parameter is not submitted the true is suggested
     * @return OrderDocumentBuilder
     */
    public function setIsTestDocument(?bool $isTestDocument = null): OrderDocumentBuilder
    {
        $this->objectHelper->tryCall($this->invoiceObject->getExchangedDocumentContext(), "setTestIndicator", $this->objectHelper->getIndicatorType($isTestDocument ?? true));
        return $this;
    }

    /**
     * Add a note to the docuzment
     *
     * @param string $content Free text on the order
     * @param string|null $subjectCode Code to qualify the free text for the order
     * @return OrderDocumentBuilder
     */
    public function addDocumentNote(string $content, ?string $subjectCode = null): OrderDocumentBuilder
    {
        $note = $this->objectHelper->getNoteType($content, null, $subjectCode);
        $this->objectHelper->tryCall($this->invoiceObject->getExchangedDocument(), "addToIncludedNote", $note);
        return $this;
    }

    /**
     * Document money summation
     *
     * @param float $grandTotalAmount Total order amount including sales tax
     * @param float|null $lineTotalAmount Sum of the net amounts of all prder items
     * @param float|null $chargeTotalAmount Sum of the surcharges at document level
     * @param float|null $allowanceTotalAmount Sum of the discounts at document level
     * @param float|null $taxBasisTotalAmount Total order amount excluding sales tax
     * @param float|null $taxTotalAmount Total amount of the order tax, Total tax amount in the booking currency
     * @return OrderDocumentBuilder
     */
    public function setDocumentSummation(float $grandTotalAmount, ?float $lineTotalAmount = null, ?float $chargeTotalAmount = null, ?float $allowanceTotalAmount = null, ?float $taxBasisTotalAmount = null, ?float $taxTotalAmount = null): OrderDocumentBuilder
    {
        $summation = $this->objectHelper->getTradeSettlementHeaderMonetarySummationType($grandTotalAmount, $lineTotalAmount, $chargeTotalAmount, $allowanceTotalAmount, $taxBasisTotalAmount, $taxTotalAmount);
        $this->objectHelper->tryCall($this->headerTradeSettlement, "setSpecifiedTradeSettlementHeaderMonetarySummation", $summation);
        $taxTotalAmount = $this->objectHelper->tryCallAndReturn($summation, "getTaxTotalAmount");
        $orderCurrencyCode = $this->objectHelper->tryCallByPathAndReturn($this->headerTradeSettlement, "getOrderCurrencyCode.value");
        $this->objectHelper->tryCall($this->objectHelper->ensureArray($taxTotalAmount)[0], 'setCurrencyID', $orderCurrencyCode);
        return $this;
    }

    /**
     * An identifier assigned by the buyer and used for internal routing.
     *
     * __Note__: The reference is specified by the buyer (e.g. contact details, department, office ID, project code),
     * but stated by the seller on the order.
     *
     * @param string $buyerreference
     * An identifier assigned by the buyer and used for internal routing
     * @return OrderDocumentBuilder
     */
    public function setDocumentBuyerReference(string $buyerreference): OrderDocumentBuilder
    {
        $reference = $this->objectHelper->getTextType($buyerreference);
        $this->objectHelper->tryCall($this->headerTradeAgreement, "setBuyerReference", $reference);
        return $this;
    }

    /**
     * Detailed information about the seller (=service provider)
     *
     * @param string $name The full formal name under which the seller is registered in the
     * National Register of Legal Entities, Taxable Person or otherwise acting as person(s)
     * @param string|null $id
     * An identifier of the seller. In many systems, seller identification
     * is key information. Multiple seller IDs can be assigned or specified. They can be differentiated
     * by using different identification schemes. If no scheme is given, it should be known to the buyer
     * and seller, e.g. a previously exchanged, buyer-assigned identifier of the seller
     * @param string|null $description
     * Further legal information that is relevant for the seller
     * @return OrderDocumentBuilder
     */
    public function setDocumentSeller(string $name, ?string $id = null, ?string $description = null): OrderDocumentBuilder
    {
        $sellerTradeParty = $this->objectHelper->getTradeParty($name, $id, $description);
        $this->objectHelper->tryCall($this->headerTradeAgreement, "setSellerTradeParty", $sellerTradeParty);
        return $this;
    }

    /**
     * Add a global id for the seller
     *
     * __Notes__
     *
     * - The Seller's ID identification scheme is a unique identifier
     *   assigned to a seller by a global registration organization
     *
     * @param string $globalID
     * The seller's identifier identification scheme is an identifier uniquely assigned to a seller by a
     * global registration organization.
     * @param string $globalIDType
     * If the identifier is used for the identification scheme, it must be selected from the entries in
     * the list published by the ISO / IEC 6523 Maintenance Agency.
     * @return OrderDocumentBuilder
     */
    public function addDocumentSellerGlobalId(string $globalID, string $globalIDType): OrderDocumentBuilder
    {
        $sellerTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeAgreement, "getSellerTradeParty");
        $this->objectHelper->tryCall($sellerTradeParty, "addToGlobalID", $this->objectHelper->getIdType($globalID, $globalIDType));
        return $this;
    }

    /**
     * Add detailed information on the seller's tax information
     *
     * The local identification (defined by the seller's address) of the seller for tax purposes or a reference that enables the seller
     * to indicate his reporting status for tax purposes The sales tax identification number of the seller
     * Note: This information may affect how the buyer the order settled (such as in relation to social security contributions). So
     * e.g. In some countries, if the seller is not reported for tax, the buyer will withhold the tax amount and pay it on behalf of the
     * seller. Sales tax number with a prefixed country code. A supplier registered as subject to VAT must provide his sales tax
     * identification number, unless he uses a tax agent.
     *
     * @param string $taxregtype
     * Type of tax number of the seller
     * @param string $taxregid
     * Tax number of the seller or sales tax identification number of the (FC = Tax number, VA = Sales tax number)
     * @return OrderDocumentBuilder
     */
    public function addDocumentSellerTaxRegistration(string $taxregtype, string $taxregid): OrderDocumentBuilder
    {
        $sellerTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeAgreement, "getSellerTradeParty");
        $taxreg = $this->objectHelper->getTaxRegistrationType($taxregtype, $taxregid);
        $this->objectHelper->tryCall($sellerTradeParty, "addToSpecifiedTaxRegistration", $taxreg);
        return $this;
    }

    /**
     * Sets detailed information on the business address of the seller
     *
     * @param string|null $lineone
     * The main line in the sellers address. This is usually the street name and house number or
     * the post office box
     * @param string|null $linetwo
     * Line 2 of the seller's address. This is an additional address line in an address that can be
     * used to provide additional details in addition to the main line
     * @param string|null $linethree
     * Line 3 of the seller's address. This is an additional address line in an address that can be
     * used to provide additional details in addition to the main line
     * @param string|null $postcode
     * Identifier for a group of properties, such as a zip code
     * @param string|null $city
     * Usual name of the city or municipality in which the seller's address is located
     * @param string|null $country
     * Code used to identify the country. If no tax agent is specified, this is the country in which the sales tax
     * is due. The lists of approved countries are maintained by the EN ISO 3166-1 Maintenance Agency “Codes for the
     * representation of names of countries and their subdivisions”
     * @param string|null $subdivision
     * The sellers state
     * @return OrderDocumentBuilder
     */
    public function setDocumentSellerAddress(?string $lineone = null, ?string $linetwo = null, ?string $linethree = null, ?string $postcode = null, ?string $city = null, ?string $country = null, ?string $subdivision = null): OrderDocumentBuilder
    {
        $sellerTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeAgreement, "getSellerTradeParty");
        $address = $this->objectHelper->getTradeAddress($lineone, $linetwo, $linethree, $postcode, $city, $country, $subdivision);
        $this->objectHelper->tryCall($sellerTradeParty, "setPostalTradeAddress", $address);
        return $this;
    }

    /**
     * Set Organization details
     *
     * @param string|null $legalorgid
     * An identifier issued by an official registrar that identifies the
     * seller as a legal entity or legal person. If no identification scheme ($legalorgtype) is provided,
     * it should be known to the buyer and seller
     * @param string|null $legalorgtype
     * The identifier for the identification scheme of the legal
     * registration of the seller. If the identification scheme is used, it must be selected from
     * ISO/IEC 6523 list
     * @param string|null $legalorgname
     * A name by which the seller is known, if different from the seller's name (also known as
     * the company name). Note: This may be used if different from the seller's name.
     * @return OrderDocumentBuilder
     */
    public function setDocumentSellerLegalOrganisation(?string $legalorgid = null, ?string $legalorgtype = null, ?string $legalorgname = null): OrderDocumentBuilder
    {
        $sellerTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeAgreement, "getSellerTradeParty");
        $legalorg = $this->objectHelper->getLegalOrganization($legalorgid, $legalorgtype, $legalorgname);
        $this->objectHelper->tryCall($sellerTradeParty, "setSpecifiedLegalOrganization", $legalorg);
        return $this;
    }

    /**
     * Set detailed information on the seller's contact person
     *
     * @param string|null $contactpersonname
     * Contact point for a legal entity,
     * such as a personal name of the contact person
     * @param string|null $contactdepartmentname
     * Contact point for a legal entity, such as a name of the department or office
     * @param string|null $contactphoneno
     * Detailed information on the seller's phone number
     * @param string|null $contactfaxno
     * Detailed information on the seller's fax number
     * @param string|null $contactemailadd
     * Detailed information on the seller's email address
     * @param string|null $contactTypeCode
     * Type of the contact
     * @return OrderDocumentBuilder
     */
    public function setDocumentSellerContact(?string $contactpersonname = null, ?string $contactdepartmentname = null, ?string $contactphoneno = null, ?string $contactfaxno = null, ?string $contactemailadd = null, ?string $contactTypeCode = null): OrderDocumentBuilder
    {
        $sellerTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeAgreement, "getSellerTradeParty");
        $contact = $this->objectHelper->getTradeContact($contactpersonname, $contactdepartmentname, $contactphoneno, $contactfaxno, $contactemailadd, $contactTypeCode);
        $this->objectHelper->tryCallIfMethodExists($sellerTradeParty, "addToDefinedTradeContact", "setDefinedTradeContact", [$contact], $contact);
        return $this;
    }

    /**
     * Set the universal communication info for the seller
     *
     * @param string|null $uriType
     * @param string|null $uriId
     * @return OrderDocumentBuilder
     */
    public function setDocumentSellerUniversalCommunication(?string $uriType = null, ?string $uriId = null): OrderDocumentBuilder
    {
        $sellerTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeAgreement, "getSellerTradeParty");
        $universalCommunication = $this->objectHelper->getUniversalCommunicationType(null, $uriId, $uriType);
        $this->objectHelper->tryCall($sellerTradeParty, "setURIUniversalCommunication", $universalCommunication);
        return $this;
    }

    /**
     * Add additional detailed information on the seller's contact person.
     *
     * @param string|null $contactpersonname
     * Contact point for a legal entity,
     * such as a personal name of the contact person
     * @param string|null $contactdepartmentname
     * Contact point for a legal entity, such as a name of the department or office
     * @param string|null $contactphoneno
     * Detailed information on the seller's phone number
     * @param string|null $contactfaxno
     * Detailed information on the seller's fax number
     * @param string|null $contactemailadd
     * Detailed information on the seller's email address
     * @param string|null $contactTypeCode
     * Type of the contact
     * @return OrderDocumentBuilder
     */
    public function addDocumentSellerContact(?string $contactpersonname = null, ?string $contactdepartmentname = null, ?string $contactphoneno = null, ?string $contactfaxno = null, ?string $contactemailadd = null, ?string $contactTypeCode = null): OrderDocumentBuilder
    {
        $sellerTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeAgreement, "getSellerTradeParty");
        $contact = $this->objectHelper->getTradeContact($contactpersonname, $contactdepartmentname, $contactphoneno, $contactfaxno, $contactemailadd, $contactTypeCode);
        $this->objectHelper->tryCall($sellerTradeParty, "addToDefinedTradeContact", $contact);
        return $this;
    }

    /**
     * Detailed information about the buyer (service recipient)
     *
     * @param string $name
     * The full name of the buyer
     * @param string|null $id
     * An identifier of the buyer. In many systems, buyer identification is key information. Multiple buyer IDs can be
     * assigned or specified. They can be differentiated by using different identification schemes. If no scheme is given,
     * it should be known to the buyer and buyer, e.g. a previously exchanged, seller-assigned identifier of the buyer
     * @param string|null $description
     * Further legal information about the buyer
     * @return OrderDocumentBuilder
     */
    public function setDocumentBuyer(string $name, ?string $id = null, ?string $description = null): OrderDocumentBuilder
    {
        $buyerTradeParty = $this->objectHelper->getTradeParty($name, $id, $description);
        $this->objectHelper->tryCall($this->headerTradeAgreement, "setBuyerTradeParty", $buyerTradeParty);
        return $this;
    }

    /**
     * Add a global id for the buyer
     *
     * @param string $globalID
     * The buyers's identifier identification scheme is an identifier uniquely assigned to a buyer by a
     * global registration organization.
     * @param string $globalIDType
     * If the identifier is used for the identification scheme, it must be selected from the entries in
     * the list published by the ISO / IEC 6523 Maintenance Agency.
     * @return OrderDocumentBuilder
     */
    public function addDocumentBuyerGlobalId(string $globalID, string $globalIDType): OrderDocumentBuilder
    {
        $buyerTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeAgreement, "getBuyerTradeParty");
        $this->objectHelper->tryCall($buyerTradeParty, "addToGlobalID", $this->objectHelper->getIdType($globalID, $globalIDType));
        return $this;
    }

    /**
     * Add detailed information on the buyers's tax information
     *
     * The local identification (defined by the buyers's address) of the buyers for tax purposes or a reference that enables the buyers
     * to indicate his reporting status for tax purposes The sales tax identification number of the buyers
     * Note: This information may affect how the buyer the invoice settled (such as in relation to social security contributions). So
     * e.g. In some countries, if the buyers is not reported for tax, the buyer will withhold the tax amount and pay it on behalf of the
     * buyers. Sales tax number with a prefixed country code. A supplier registered as subject to VAT must provide his sales tax
     * identification number, unless he uses a tax agent.
     *
     * @param string $taxregtype
     * Type of tax number of the buyers
     * @param string $taxregid
     * Tax number of the buyers or sales tax identification number of the (FC = Tax number, VA = Sales tax number)
     * @return OrderDocumentBuilder
     */
    public function addDocumentBuyerTaxRegistration(string $taxregtype, string $taxregid): OrderDocumentBuilder
    {
        $buyerTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeAgreement, "getBuyerTradeParty");
        $taxreg = $this->objectHelper->getTaxRegistrationType($taxregtype, $taxregid);
        $this->objectHelper->tryCall($buyerTradeParty, "addToSpecifiedTaxRegistration", $taxreg);
        return $this;
    }

    /**
     * Sets detailed information on the business address of the buyer
     *
     * @param string|null $lineone
     * The main line in the buyers address. This is usually the street name and house number or
     * the post office box
     * @param string|null $linetwo
     * Line 2 of the buyers address. This is an additional address line in an address that can be
     * used to provide additional details in addition to the main line
     * @param string|null $linethree
     * Line 3 of the buyers address. This is an additional address line in an address that can be
     * used to provide additional details in addition to the main line
     * @param string|null $postcode
     * Identifier for a group of properties, such as a zip code
     * @param string|null $city
     * Usual name of the city or municipality in which the buyers address is located
     * @param string|null $country
     * Code used to identify the country. If no tax agent is specified, this is the country in which the sales tax
     * is due. The lists of approved countries are maintained by the EN ISO 3166-1 Maintenance Agency “Codes for the
     * representation of names of countries and their subdivisions”
     * @param string|null $subdivision
     * The buyers state
     * @return OrderDocumentBuilder
     */
    public function setDocumentBuyerAddress(?string $lineone = null, ?string $linetwo = null, ?string $linethree = null, ?string $postcode = null, ?string $city = null, ?string $country = null, ?string $subdivision = null): OrderDocumentBuilder
    {
        $buyerTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeAgreement, "getBuyerTradeParty");
        $address = $this->objectHelper->getTradeAddress($lineone, $linetwo, $linethree, $postcode, $city, $country, $subdivision);
        $this->objectHelper->tryCall($buyerTradeParty, "setPostalTradeAddress", $address);
        return $this;
    }

    /**
     * Set legal organisation of the buyer party
     *
     * @param string|null $legalorgid
     * An identifier issued by an official registrar that identifies the
     * buyer as a legal entity or legal person. If no identification scheme ($legalorgtype) is provided,
     * it should be known to the buyer and buyer
     * @param string|null $legalorgtype
     * The identifier for the identification scheme of the legal
     * registration of the buyer. If the identification scheme is used, it must be selected from
     * ISO/IEC 6523 list
     * @param string|null $legalorgname
     * A name by which the buyer is known, if different from the buyers name
     * (also known as the company name)
     * @return OrderDocumentBuilder
     */
    public function setDocumentBuyerLegalOrganisation(?string $legalorgid = null, ?string $legalorgtype = null, ?string $legalorgname = null): OrderDocumentBuilder
    {
        $buyerTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeAgreement, "getBuyerTradeParty");
        $legalorg = $this->objectHelper->getLegalOrganization($legalorgid, $legalorgtype, $legalorgname);
        $this->objectHelper->tryCall($buyerTradeParty, "setSpecifiedLegalOrganization", $legalorg);
        return $this;
    }

    /**
     * Set contact of the buyer party
     *
     * @param string|null $contactpersonname
     * Contact point for a legal entity, such as a personal name of the contact person
     * @param string|null $contactdepartmentname
     * Contact point for a legal entity, such as a name of the department or office
     * @param string|null $contactphoneno
     * Detailed information on the buyer's phone number
     * @param string|null $contactfaxno
     * Detailed information on the buyer's fax number
     * @param string|null $contactemailadd
     * Detailed information on the buyer's email address
     * @param string|null $contactTypeCode
     *
     * @return OrderDocumentBuilder
     */
    public function setDocumentBuyerContact(?string $contactpersonname = null, ?string $contactdepartmentname = null, ?string $contactphoneno = null, ?string $contactfaxno = null, ?string $contactemailadd = null, ?string $contactTypeCode = null): OrderDocumentBuilder
    {
        $buyerTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeAgreement, "getBuyerTradeParty");
        $contact = $this->objectHelper->getTradeContact($contactpersonname, $contactdepartmentname, $contactphoneno, $contactfaxno, $contactemailadd, $contactTypeCode);
        $this->objectHelper->tryCallIfMethodExists($buyerTradeParty, "addToDefinedTradeContact", "setDefinedTradeContact", [$contact], $contact);
        return $this;
    }

    /**
     * Add additional contact of the buyer party. This only supported in the
     *
     * @param string|null $contactpersonname
     * Contact point for a legal entity, such as a personal name of the contact person
     * @param string|null $contactdepartmentname
     * Contact point for a legal entity, such as a name of the department or office
     * @param string|null $contactphoneno
     * Detailed information on the buyer's phone number
     * @param string|null $contactfaxno
     * Detailed information on the buyer's fax number
     * @param string|null $contactemailadd
     * Detailed information on the buyer's email address
     * @param string|null $contactTypeCode
     *
     * @return OrderDocumentBuilder
     */
    public function addDocumentBuyerContact(?string $contactpersonname = null, ?string $contactdepartmentname = null, ?string $contactphoneno = null, ?string $contactfaxno = null, ?string $contactemailadd = null, ?string $contactTypeCode = null): OrderDocumentBuilder
    {
        $buyerTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeAgreement, "getBuyerTradeParty");
        $contact = $this->objectHelper->getTradeContact($contactpersonname, $contactdepartmentname, $contactphoneno, $contactfaxno, $contactemailadd, $contactTypeCode);
        $this->objectHelper->tryCall($buyerTradeParty, "addToDefinedTradeContact", $contact);
        return $this;
    }

    /**
     * Set the universal communication info for the Buyer
     *
     * @param string|null $uriType
     * @param string|null $uriId
     * @return OrderDocumentBuilder
     */
    public function setDocumentBuyerUniversalCommunication(?string $uriType = null, ?string $uriId = null): OrderDocumentBuilder
    {
        $buyerTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeAgreement, "getBuyerTradeParty");
        $universalCommunication = $this->objectHelper->getUniversalCommunicationType(null, $uriId, $uriType);
        $this->objectHelper->tryCall($buyerTradeParty, "setURIUniversalCommunication", $universalCommunication);
        return $this;
    }

    /**
     * Detailed information about the party who raises the Order originally on behalf of the Buyer
     *
     * @param string $name
     * The full name of the buyer
     * @param string|null $id
     * An identifier of the buyer. In many systems, buyer identification is key information. Multiple buyer IDs can be
     * assigned or specified. They can be differentiated by using different identification schemes. If no scheme is given,
     * it should be known to the buyer and buyer, e.g. a previously exchanged, seller-assigned identifier of the buyer
     * @param string|null $description
     * Further legal information about the buyer
     * @return OrderDocumentBuilder
     */
    public function setDocumentBuyerRequisitioner(string $name, ?string $id = null, ?string $description = null): OrderDocumentBuilder
    {
        $buyerRequisitionerTradeParty = $this->objectHelper->getTradeParty($name, $id, $description);
        $this->objectHelper->tryCall($this->headerTradeAgreement, "setBuyerRequisitionerTradeParty", $buyerRequisitionerTradeParty);
        return $this;
    }

    /**
     * Add a global id for the party who raises the Order originally on behalf of the Buyer
     *
     * @param string $globalID
     * The buyers's identifier identification scheme is an identifier uniquely assigned to a buyer by a
     * global registration organization.
     * @param string $globalIDType
     * If the identifier is used for the identification scheme, it must be selected from the entries in
     * the list published by the ISO / IEC 6523 Maintenance Agency.
     * @return OrderDocumentBuilder
     */
    public function addDocumentBuyerRequisitionerGlobalId(string $globalID, string $globalIDType): OrderDocumentBuilder
    {
        $buyerRequisitionerTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeAgreement, "getBuyerRequisitionerTradeParty");
        $this->objectHelper->tryCall($buyerRequisitionerTradeParty, "addToGlobalID", $this->objectHelper->getIdType($globalID, $globalIDType));
        return $this;
    }

    /**
     * Add tax registration information of the party who raises the Order originally on behalf of the Buyer
     *
     * The local identification (defined by the buyers's address) of the buyers for tax purposes or a reference that enables the buyers
     * to indicate his reporting status for tax purposes The sales tax identification number of the buyers
     * Note: This information may affect how the buyer the invoice settled (such as in relation to social security contributions). So
     * e.g. In some countries, if the buyers is not reported for tax, the buyer will withhold the tax amount and pay it on behalf of the
     * buyers. Sales tax number with a prefixed country code. A supplier registered as subject to VAT must provide his sales tax
     * identification number, unless he uses a tax agent.
     *
     * @param string $taxregtype
     * Type of tax number of the buyers
     * @param string $taxregid
     * Tax number of the buyers or sales tax identification number of the (FC = Tax number, VA = Sales tax number)
     * @return OrderDocumentBuilder
     */
    public function addDocumentBuyerRequisitionerTaxRegistration(string $taxregtype, string $taxregid): OrderDocumentBuilder
    {
        $buyerRequisitionerTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeAgreement, "getBuyerRequisitionerTradeParty");
        $taxreg = $this->objectHelper->getTaxRegistrationType($taxregtype, $taxregid);
        $this->objectHelper->tryCall($buyerRequisitionerTradeParty, "addToSpecifiedTaxRegistration", $taxreg);
        return $this;
    }

    /**
     * Sets detailed information of the party who raises the Order originally on behalf of the Buyer
     *
     * @param string|null $lineone
     * The main line in the buyers address. This is usually the street name and house number or
     * the post office box
     * @param string|null $linetwo
     * Line 2 of the buyers address. This is an additional address line in an address that can be
     * used to provide additional details in addition to the main line
     * @param string|null $linethree
     * Line 3 of the buyers address. This is an additional address line in an address that can be
     * used to provide additional details in addition to the main line
     * @param string|null $postcode
     * Identifier for a group of properties, such as a zip code
     * @param string|null $city
     * Usual name of the city or municipality in which the buyers address is located
     * @param string|null $country
     * Code used to identify the country. If no tax agent is specified, this is the country in which the sales tax
     * is due. The lists of approved countries are maintained by the EN ISO 3166-1 Maintenance Agency “Codes for the
     * representation of names of countries and their subdivisions”
     * @param string|null $subdivision
     * The buyers state
     * @return OrderDocumentBuilder
     */
    public function setDocumentBuyerRequisitionerAddress(?string $lineone = null, ?string $linetwo = null, ?string $linethree = null, ?string $postcode = null, ?string $city = null, ?string $country = null, ?string $subdivision = null): OrderDocumentBuilder
    {
        $buyerRequisitionerTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeAgreement, "getBuyerRequisitionerTradeParty");
        $address = $this->objectHelper->getTradeAddress($lineone, $linetwo, $linethree, $postcode, $city, $country, $subdivision);
        $this->objectHelper->tryCall($buyerRequisitionerTradeParty, "setPostalTradeAddress", $address);
        return $this;
    }

    /**
     * Set legal organisation of the party who raises the Order originally on behalf of the Buyer
     *
     * @param string|null $legalorgid
     * An identifier issued by an official registrar that identifies the
     * buyer as a legal entity or legal person. If no identification scheme ($legalorgtype) is provided,
     * it should be known to the buyer and buyer
     * @param string|null $legalorgtype
     * The identifier for the identification scheme of the legal
     * registration of the buyer. If the identification scheme is used, it must be selected from
     * ISO/IEC 6523 list
     * @param string|null $legalorgname
     * A name by which the buyer is known, if different from the buyers name
     * (also known as the company name)
     * @return OrderDocumentBuilder
     */
    public function setDocumentBuyerRequisitionerLegalOrganisation(?string $legalorgid = null, ?string $legalorgtype = null, ?string $legalorgname = null): OrderDocumentBuilder
    {
        $buyerRequisitionerTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeAgreement, "getBuyerRequisitionerTradeParty");
        $legalorg = $this->objectHelper->getLegalOrganization($legalorgid, $legalorgtype, $legalorgname);
        $this->objectHelper->tryCall($buyerRequisitionerTradeParty, "setSpecifiedLegalOrganization", $legalorg);
        return $this;
    }

    /**
     * Set contact of the party who raises the Order originally on behalf of the Buyer
     *
     * @param string|null $contactpersonname
     * Contact point for a legal entity, such as a personal name of the contact person
     * @param string|null $contactdepartmentname
     * Contact point for a legal entity, such as a name of the department or office
     * @param string|null $contactphoneno
     * Detailed information on the buyer's phone number
     * @param string|null $contactfaxno
     * Detailed information on the buyer's fax number
     * @param string|null $contactemailadd
     * Detailed information on the buyer's email address
     * @param string|null $contactTypeCode
     * Type Code of the contact
     * @return OrderDocumentBuilder
     */
    public function setDocumentBuyerRequisitionerContact(?string $contactpersonname = null, ?string $contactdepartmentname = null, ?string $contactphoneno = null, ?string $contactfaxno = null, ?string $contactemailadd = null, ?string $contactTypeCode = null): OrderDocumentBuilder
    {
        $buyerRequisitionerTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeAgreement, "getBuyerRequisitionerTradeParty");
        $contact = $this->objectHelper->getTradeContact($contactpersonname, $contactdepartmentname, $contactphoneno, $contactfaxno, $contactemailadd, $contactTypeCode);
        $this->objectHelper->tryCallIfMethodExists($buyerRequisitionerTradeParty, "addToDefinedTradeContact", "setDefinedTradeContact", [$contact], $contact);
        return $this;
    }

    /**
     * Add additional contact of the party who raises the Order originally on behalf of the Buyer
     *
     * @param string|null $contactpersonname
     * Contact point for a legal entity, such as a personal name of the contact person
     * @param string|null $contactdepartmentname
     * Contact point for a legal entity, such as a name of the department or office
     * @param string|null $contactphoneno
     * Detailed information on the buyer's phone number
     * @param string|null $contactfaxno
     * Detailed information on the buyer's fax number
     * @param string|null $contactemailadd
     * Detailed information on the buyer's email address
     * @return OrderDocumentBuilder
     */
    public function addDocumentBuyerRequisitionerContact(?string $contactpersonname = null, ?string $contactdepartmentname = null, ?string $contactphoneno = null, ?string $contactfaxno = null, ?string $contactemailadd = null): OrderDocumentBuilder
    {
        $buyerRequisitionerTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeAgreement, "getBuyerRequisitionerTradeParty");
        $contact = $this->objectHelper->getTradeContact($contactpersonname, $contactdepartmentname, $contactphoneno, $contactfaxno, $contactemailadd);
        $this->objectHelper->tryCall($buyerRequisitionerTradeParty, "addToDefinedTradeContact", $contact);
        return $this;
    }

    /**
     * Set the universal communication info for the Buyer Requisitioner
     *
     * @param string|null $uriType
     * @param string|null $uriId
     * @return OrderDocumentBuilder
     */
    public function setDocumentBuyerRequisitionerUniversalCommunication(?string $uriType = null, ?string $uriId = null): OrderDocumentBuilder
    {
        $buyerRequisitionerTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeAgreement, "getBuyerRequisitionerTradeParty");
        $universalCommunication = $this->objectHelper->getUniversalCommunicationType(null, $uriId, $uriType);
        $this->objectHelper->tryCall($buyerRequisitionerTradeParty, "setURIUniversalCommunication", $universalCommunication);
        return $this;
    }

    /**
     * Set information on the delivery conditions
     *
     * @param string|null $code
     * The code specifying the type of delivery for these trade delivery terms. To be chosen from the entries
     * in UNTDID 4053 + INCOTERMS List
     * - 1 : Delivery arranged by the supplier (Indicates that the supplier will arrange delivery of the goods).
     * - 2 : Delivery arranged by logistic service provider (Code indicating that the logistic service provider has arranged the delivery of goods).
     * - CFR : Cost and Freight (insert named port of destination)
     * - CIF : Cost, Insurance and Freight (insert named port of destination)
     * - CIP : Carriage and Insurance Paid to (insert named place of destination)
     * - CPT : Carriage Paid To (insert named place of destination)
     * - DAP : Delivered At Place (insert named place of destination)
     * - DAT : Delivered At Terminal (insert named terminal at port or place of destination)
     * - DDP : Delivered Duty Paid (insert named place of destination)
     * - EXW : Ex Works (insert named place of delivery)
     * - FAS : Free Alongside Ship (insert named port of shipment)
     * - FCA : Free Carrier (insert named place of delivery)
     * - FOB : Free On Board (insert named port of shipment)
     * @param string|null $description
     * Simple description
     * @param string|null $functionCode
     * @param string|null $relevantTradeLocationId
     * @param string|null $relevantTradeLocationName
     * @return OrderDocumentBuilder
     */
    public function setDocumentDeliveryTerms(?string $code = null, ?string $description = null, ?string $functionCode = null, ?string $relevantTradeLocationId = null, ?string $relevantTradeLocationName = null): OrderDocumentBuilder
    {
        $deliveryterms = $this->objectHelper->getTradeDeliveryTermsType($code, $description, $functionCode, $relevantTradeLocationId, $relevantTradeLocationName);
        $this->objectHelper->tryCall($this->headerTradeAgreement, "setApplicableTradeDeliveryTerms", $deliveryterms);
        return $this;
    }

    /**
     * Set details of the associated order confirmation
     *
     * @param string $sellerOrderRefId
     * An identifier issued by the seller for a referenced sales order (Order confirmation number)
     * @param DateTime|null $sellerOrderRefDate
     * Order confirmation date
     * @return OrderDocumentBuilder
     */
    public function setDocumentSellerOrderReferencedDocument(string $sellerOrderRefId, ?DateTime $sellerOrderRefDate = null): OrderDocumentBuilder
    {
        $sellerOrderRefDoc = $this->objectHelper->getReferencedDocumentType($sellerOrderRefId, null, null, null, null, null, $sellerOrderRefDate, null);
        $this->objectHelper->tryCall($this->headerTradeAgreement, "setSellerOrderReferencedDocument", $sellerOrderRefDoc);
        return $this;
    }

    /**
     * Set details of the related buyer order
     *
     * @param string $buyerOrderRefId
     * An identifier issued by the buyer for a referenced order (order number)
     * @param DateTime|null $buyerOrderRefDate
     * Date of order
     * @return OrderDocumentBuilder
     */
    public function setDocumentBuyerOrderReferencedDocument(string $buyerOrderRefId, ?DateTime $buyerOrderRefDate = null): OrderDocumentBuilder
    {
        $buyerOrderRefDoc = $this->objectHelper->getReferencedDocumentType($buyerOrderRefId, null, null, null, null, null, $buyerOrderRefDate, null);
        $this->objectHelper->tryCall($this->headerTradeAgreement, "setBuyerOrderReferencedDocument", $buyerOrderRefDoc);
        return $this;
    }

    /**
     * Set details of the related quotation
     *
     * @param string $quotationRefId
     * An Identifier of a Quotation, issued by the Seller.
     * @param DateTime|null $quotationRefDate
     * Date of order
     * @return OrderDocumentBuilder
     */
    public function setDocumentQuotationReferencedDocument(string $quotationRefId, ?DateTime $quotationRefDate = null): OrderDocumentBuilder
    {
        $quotationRefDoc = $this->objectHelper->getReferencedDocumentType($quotationRefId, null, null, null, null, null, $quotationRefDate, null);
        $this->objectHelper->tryCall($this->headerTradeAgreement, "setQuotationReferencedDocument", $quotationRefDoc);
        return $this;
    }

    /**
     * Set details of the associated contract. The contract identifier should be unique in the context
     * of the specific trading relationship and for a defined time period.
     *
     * @param string $contractRefId
     * The contract reference should be assigned once in the context of the specific trade relationship and for a
     * defined period of time (contract number)
     * @param DateTime|null $contractRefDate
     * The formatted date or date time for the issuance of this referenced Contract.
     * @return OrderDocumentBuilder
     */
    public function setDocumentContractReferencedDocument(string $contractRefId, ?DateTime $contractRefDate = null): OrderDocumentBuilder
    {
        $contractRefDoc = $this->objectHelper->getReferencedDocumentType($contractRefId, null, null, null, null, null, $contractRefDate, null);
        $this->objectHelper->tryCallIfMethodExists($this->headerTradeAgreement, "addToContractReferencedDocument", "setContractReferencedDocument", [$contractRefDoc], $contractRefDoc);
        return $this;
    }

    /**
     * Add new details of the associated contract
     *
     * @param string $contractRefId
     * The contract reference should be assigned once in the context of the specific trade relationship and for a
     * defined period of time (contract number)
     * @param DateTime|null $contractRefDate
     * The formatted date or date time for the issuance of this referenced Contract.
     * @return OrderDocumentBuilder
     */
    public function addDocumentContractReferencedDocument(string $contractRefId, ?DateTime $contractRefDate = null): OrderDocumentBuilder
    {
        $contractRefDoc = $this->objectHelper->getReferencedDocumentType($contractRefId, null, null, null, null, null, $contractRefDate, null);
        $this->objectHelper->tryCall($this->headerTradeAgreement, "addToContractReferencedDocument", $contractRefDoc);
        return $this;
    }

    /**
     * Set details of the associated contract
     *
     * @param string $requisitionRefId
     * The identification of a Requisition Document, issued by the Buyer or the Buyer Requisitioner.
     * @param DateTime|null $requisitionRefDate
     * The formatted date or date time for the issuance of this referenced Requisition.
     * @return OrderDocumentBuilder
     */
    public function setDocumentRequisitionReferencedDocument(string $requisitionRefId, ?DateTime $requisitionRefDate = null): OrderDocumentBuilder
    {
        $requisitionRefDoc = $this->objectHelper->getReferencedDocumentType($requisitionRefId, null, null, null, null, null, $requisitionRefDate, null);
        $this->objectHelper->tryCallIfMethodExists($this->headerTradeAgreement, "addToRequisitionReferencedDocument", "setRequisitionReferencedDocument", [$requisitionRefDoc], $requisitionRefDoc);
        return $this;
    }

    /**
     * Add new details of the associated contract
     *
     * @param string $requisitionRefId
     * The identification of a Requisition Document, issued by the Buyer or the Buyer Requisitioner.
     * @param DateTime|null $requisitionRefDate
     * The formatted date or date time for the issuance of this referenced Requisition.
     * @return OrderDocumentBuilder
     */
    public function addDocumentRequisitionReferencedDocument(string $requisitionRefId, ?DateTime $requisitionRefDate = null): OrderDocumentBuilder
    {
        $requisitionRefDoc = $this->objectHelper->getReferencedDocumentType($requisitionRefId, null, null, null, null, null, $requisitionRefDate, null);
        $this->objectHelper->tryCall($this->headerTradeAgreement, "addToRequisitionReferencedDocument", $requisitionRefDoc);
        return $this;
    }

    /**
     * Set information about billing documents that provide evidence of claims made in the bill
     *
     * __Notes__
     * The additional supporting documents can be used for both referencing a document number which
     * is expected to be known by the receiver, an external document (referenced by a URL) or as an
     * embedded document (such as a time report in pdf). The option to link to an external document
     * will be needed, for example in the case of large attachments and/or when sensitive information,
     * e.g. person-related services, has to be separated from the order itself.
     *
     * @param string $additionalRefTypeCode
     * Type of referenced document (See codelist UNTDID 1001)
     *  - Code 916 "reference paper" is used to reference the identification of the document on which the invoice is based
     *  - Code 50 "Price / sales catalog response" is used to reference the tender or the lot
     *  - Code 130 "invoice data sheet" is used to reference an identifier for an object specified by the seller.
     * @param string|null $additionalRefId
     * The identifier of the tender or lot to which the invoice relates, or an identifier specified by the seller for
     * an object on which the invoice is based, or an identifier of the document on which the invoice is based.
     * @param string|null $additionalRefURIID
     * The Uniform Resource Locator (URL) at which the external document is available. A means of finding the resource
     * including the primary access method intended for it, e.g. http: // or ftp: //. The location of the external document
     * must be used if the buyer needs additional information to support the amounts billed. External documents are not part
     * of the invoice. Access to external documents can involve certain risks.
     * @param string|array|null $additionalRefName
     * A description of the document, e.g. Hourly billing, usage or consumption report, etc.
     * @param string|null $additionalRefRefTypeCode
     * The identifier for the identification scheme of the identifier of the item invoiced. If it is not clear to the
     * recipient which scheme is used for the identifier, an identifier of the scheme should be used, which must be selected
     * from UNTDID 1153 in accordance with the code list entries.
     * @param DateTime|null $additionalRefDate
     * Document date
     * @param string|null $binarydatafilename
     * Contains a file name of an attachment document embedded as a binary object
     * @return OrderDocumentBuilder
     */
    public function addDocumentAdditionalReferencedDocument(string $additionalRefTypeCode, ?string $additionalRefId, ?string $additionalRefURIID = null, $additionalRefName = null, ?string $additionalRefRefTypeCode = null, ?DateTime $additionalRefDate = null, ?string $binarydatafilename = null): OrderDocumentBuilder
    {
        $additionalRefDoc = $this->objectHelper->getReferencedDocumentType($additionalRefId, $additionalRefURIID, null, $additionalRefTypeCode, $additionalRefName, $additionalRefRefTypeCode, $additionalRefDate, $binarydatafilename);
        $this->objectHelper->tryCall($this->headerTradeAgreement, "addToAdditionalReferencedDocument", $additionalRefDoc);
        return $this;
    }

    /**
     * Set details of a blanket order referenced document
     *
     * @param string $blanketOrderRefId
     * The unique identifier of a Blanket Order referenced document
     * @return OrderDocumentBuilder
     */
    public function setDocumentBlanketOrderReferencedDocument(string $blanketOrderRefId): OrderDocumentBuilder
    {
        $blanketOrderRefDoc = $this->objectHelper->getReferencedDocumentType($blanketOrderRefId, null, null, null, null, null, null, null);
        $this->objectHelper->tryCallIfMethodExists($this->headerTradeAgreement, "addToBlanketOrderReferencedDocument", "setBlanketOrderReferencedDocument", [$blanketOrderRefDoc], $blanketOrderRefDoc);
        return $this;
    }

    /**
     * Add new details of a blanket order referenced document
     *
     * @param string $blanketOrderRefId
     * The unique identifier of a line in the Blanketl Order referenced document
     * @return OrderDocumentBuilder
     */
    public function addDocumentBlanketOrderReferencedDocument(string $blanketOrderRefId): OrderDocumentBuilder
    {
        $blanketOrderRefDoc = $this->objectHelper->getReferencedDocumentType($blanketOrderRefId, null, null, null, null, null, null, null);
        $this->objectHelper->tryCall($this->headerTradeAgreement, "addToBlanketOrderReferencedDocument", $blanketOrderRefDoc);
        return $this;
    }

    /**
     * Set details of a the Previous Order Change Document, issued by the Buyer or the Buyer Requisitioner.
     *
     * @param string $prevOrderChangeRefId
     * The identification of a the Previous Order Change Document, issued by the Buyer or the Buyer Requisitioner.
     * @param DateTime|null $prevOrderChangeRefDate
     * Issued date
     * @return OrderDocumentBuilder
     */
    public function setDocumentPreviousOrderChangeReferencedDocument(string $prevOrderChangeRefId, ?DateTime $prevOrderChangeRefDate = null): OrderDocumentBuilder
    {
        $prevOrderChangeRefDoc = $this->objectHelper->getReferencedDocumentType($prevOrderChangeRefId, null, null, null, null, null, $prevOrderChangeRefDate, null);
        $this->objectHelper->tryCallIfMethodExists($this->headerTradeAgreement, "addToPreviousOrderChangeReferencedDocument", "setPreviousOrderChangeReferencedDocument", [$prevOrderChangeRefDoc], $prevOrderChangeRefDoc);
        return $this;
    }

    /**
     * Add new details of a the Previous Order Change Document, issued by the Buyer or the Buyer Requisitioner.
     *
     * @param string $prevOrderChangeRefId
     * The identification of a the Previous Order Change Document, issued by the Buyer or the Buyer Requisitioner.
     * @param DateTime|null $prevOrderChangeRefDate
     * Issued date
     * @return OrderDocumentBuilder
     */
    public function addDocumentPreviousOrderChangeReferencedDocument(string $prevOrderChangeRefId, ?DateTime $prevOrderChangeRefDate = null): OrderDocumentBuilder
    {
        $prevOrderChangeRefDoc = $this->objectHelper->getReferencedDocumentType($prevOrderChangeRefId, null, null, null, null, null, $prevOrderChangeRefDate, null);
        $this->objectHelper->tryCall($this->headerTradeAgreement, "addToPreviousOrderChangeReferencedDocument", $prevOrderChangeRefDoc);
        return $this;
    }

    /**
     * Set details of a the Previous Order Response Document, issued by the Seller.
     *
     * @param string $prevOrderResponseRefId
     * The identification of a the Previous Order Response Document, issued by the Seller.
     * @param DateTime|null $prevOrderResponseRefDate
     * Issued date
     * @return OrderDocumentBuilder
     */
    public function setDocumentPreviousOrderResponseReferencedDocument(string $prevOrderResponseRefId, ?DateTime $prevOrderResponseRefDate = null): OrderDocumentBuilder
    {
        $prevOrderResponseRefDoc = $this->objectHelper->getReferencedDocumentType($prevOrderResponseRefId, null, null, null, null, null, $prevOrderResponseRefDate, null);
        $this->objectHelper->tryCallIfMethodExists($this->headerTradeAgreement, "addToPreviousOrderResponseReferencedDocument", "setPreviousOrderResponseReferencedDocument", [$prevOrderResponseRefDoc], $prevOrderResponseRefDoc);
        return $this;
    }

    /**
     * Add new details of a the Previous Order Response Document, issued by the Seller.
     *
     * @param string $prevOrderResponseRefId
     * The identification of a the Previous Order Response Document, issued by the Seller.
     * @param DateTime|null $prevOrderResponseRefDate
     * Issued date
     * @return OrderDocumentBuilder
     */
    public function addDocumentPreviousOrderResponseReferencedDocument(string $prevOrderResponseRefId, ?DateTime $prevOrderResponseRefDate = null): OrderDocumentBuilder
    {
        $prevOrderResponseRefDoc = $this->objectHelper->getReferencedDocumentType($prevOrderResponseRefId, null, null, null, null, null, $prevOrderResponseRefDate, null);
        $this->objectHelper->tryCall($this->headerTradeAgreement, "addToPreviousOrderResponseReferencedDocument", $prevOrderResponseRefDoc);
        return $this;
    }

    /**
     * Set Details of a project reference
     *
     * @param string $procuringProjectId
     * Project Data
     * @param string $procuringProjectName
     * Project Name
     * @return OrderDocumentBuilder
     */
    public function setDocumentProcuringProject(string $procuringProjectId, string $procuringProjectName): OrderDocumentBuilder
    {
        $procuringProject = $this->objectHelper->getProcuringProjectType($procuringProjectId, $procuringProjectName);
        $this->objectHelper->tryCall($this->headerTradeAgreement, "setSpecifiedProcuringProject", $procuringProject);
        return $this;
    }

    /**
     * Ship-To
     *
     * @param string $name
     * The name of the party to whom the goods are being delivered or for whom the services are being
     * performed. Must be used if the recipient of the goods or services is not the same as the buyer.
     * @param string|null $id
     * An identifier for the place where the goods are delivered or where the services are provided.
     * Multiple IDs can be assigned or specified. They can be differentiated by using different
     * identification schemes. If no scheme is given, it should be known to the buyer and seller, e.g.
     * a previously exchanged identifier assigned by the buyer or seller.
     * @param string|null $description
     * Further legal information that is relevant for the party
     * @return OrderDocumentBuilder
     */
    public function setDocumentShipTo(string $name, ?string $id = null, ?string $description = null): OrderDocumentBuilder
    {
        $shipToTradeParty = $this->objectHelper->getTradeParty($name, $id, $description);
        $this->objectHelper->tryCall($this->headerTradeDelivery, "setShipToTradeParty", $shipToTradeParty);
        return $this;
    }

    /**
     * Add a global id for the Ship-to Trade Party
     *
     * @param string $globalID
     * Global identifier of the goods recipient
     * @param string $globalIDType
     * Type of global identification number, must be selected from the entries in
     * the list published by the ISO / IEC 6523 Maintenance Agency.
     * @return OrderDocumentBuilder
     */
    public function addDocumentShipToGlobalId(string $globalID, string $globalIDType): OrderDocumentBuilder
    {
        $shipToTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeDelivery, "getShipToTradeParty");
        $this->objectHelper->tryCall($shipToTradeParty, "addToGlobalID", $this->objectHelper->getIdType($globalID, $globalIDType));
        return $this;
    }

    /**
     * Add Tax registration to Ship-To Trade party
     *
     * @param string $taxregtype
     * Type of tax number of the party
     * @param string $taxregid
     * Tax number of the party or sales tax identification number of the (FC = Tax number, VA = Sales tax number)
     * @return OrderDocumentBuilder
     */
    public function addDocumentShipToTaxRegistration(string $taxregtype, string $taxregid): OrderDocumentBuilder
    {
        $shipToTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeDelivery, "getShipToTradeParty");
        $taxreg = $this->objectHelper->getTaxRegistrationType($taxregtype, $taxregid);
        $this->objectHelper->tryCall($shipToTradeParty, "addToSpecifiedTaxRegistration", $taxreg);
        return $this;
    }

    /**
     * Sets the postal address of the Ship-To party
     *
     * @param string|null $lineone
     * The main line in the party's address. This is usually the street name and house number or
     * the post office box
     * @param string|null $linetwo
     * Line 2 of the party's address. This is an additional address line in an address that can be
     * used to provide additional details in addition to the main line
     * @param string|null $linethree
     * Line 3 of the party's address. This is an additional address line in an address that can be
     * used to provide additional details in addition to the main line
     * @param string|null $postcode
     * Identifier for a group of properties, such as a zip code
     * @param string|null $city
     * Usual name of the city or municipality in which the party's address is located
     * @param string|null $country
     * Code used to identify the country. If no tax agent is specified, this is the country in which the sales tax
     * is due. The lists of approved countries are maintained by the EN ISO 3166-1 Maintenance Agency “Codes for the
     * representation of names of countries and their subdivisions”
     * @param string|null $subdivision
     * The party's state
     * @return OrderDocumentBuilder
     */
    public function setDocumentShipToAddress(?string $lineone = null, ?string $linetwo = null, ?string $linethree = null, ?string $postcode = null, ?string $city = null, ?string $country = null, ?string $subdivision = null): OrderDocumentBuilder
    {
        $shipToTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeDelivery, "getShipToTradeParty");
        $address = $this->objectHelper->getTradeAddress($lineone, $linetwo, $linethree, $postcode, $city, $country, $subdivision);
        $this->objectHelper->tryCall($shipToTradeParty, "setPostalTradeAddress", $address);
        return $this;
    }

    /**
     * Set legal organisation of the Ship-To party
     *
     * @param string|null $legalorgid
     * An identifier issued by an official registrar that identifies the
     * party as a legal entity or legal person. If no identification scheme ($legalorgtype) is provided,
     * it should be known to the buyer or seller party
     * @param string|null $legalorgtype The identifier for the identification scheme of the legal
     * registration of the party. In particular, the following scheme codes are used: 0021 : SWIFT, 0088 : EAN,
     * 0060 : DUNS, 0177 : ODETTE
     * @param string|null $legalorgname A name by which the party is known, if different from the party's name
     * (also known as the company name)
     * @return OrderDocumentBuilder
     */
    public function setDocumentShipToLegalOrganisation(?string $legalorgid = null, ?string $legalorgtype = null, ?string $legalorgname = null): OrderDocumentBuilder
    {
        $shipToTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeDelivery, "getShipToTradeParty");
        $legalorg = $this->objectHelper->getLegalOrganization($legalorgid, $legalorgtype, $legalorgname);
        $this->objectHelper->tryCall($shipToTradeParty, "setSpecifiedLegalOrganization", $legalorg);
        return $this;
    }

    /**
     * Set contact of the Ship-To party. All formerly assigned contacts will be
     * overwritten
     *
     * @param string|null $contactpersonname
     * Contact point for a legal entity, such as a personal name of the contact person
     * @param string|null $contactdepartmentname
     * Contact point for a legal entity, such as a name of the department or office
     * @param string|null $contactphoneno
     * Detailed information on the party's phone number
     * @param string|null $contactfaxno
     * Detailed information on the party's fax number
     * @param string|null $contactemailadd
     * Detailed information on the party's email address
     * @param string|null $contactTypeCode
     * Type (Code) of the contach
     * @return OrderDocumentBuilder
     */
    public function setDocumentShipToContact(?string $contactpersonname = null, ?string $contactdepartmentname = null, ?string $contactphoneno = null, ?string $contactfaxno = null, ?string $contactemailadd = null, ?string $contactTypeCode = null): OrderDocumentBuilder
    {
        $shipToTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeDelivery, "getShipToTradeParty");
        $contact = $this->objectHelper->getTradeContact($contactpersonname, $contactdepartmentname, $contactphoneno, $contactfaxno, $contactemailadd, $contactTypeCode);
        $this->objectHelper->tryCallIfMethodExists($shipToTradeParty, "addToDefinedTradeContact", "setDefinedTradeContact", [$contact], $contact);
        return $this;
    }

    /**
     * Set the universal communication info for the Buyer Requisitioner
     *
     * @param string|null $uriType
     * @param string|null $uriId
     * @return OrderDocumentBuilder
     */
    public function setDocumentShipToUniversalCommunication(?string $uriType = null, ?string $uriId = null): OrderDocumentBuilder
    {
        $shipToTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeDelivery, "getShipToTradeParty");
        $universalCommunication = $this->objectHelper->getUniversalCommunicationType(null, $uriId, $uriType);
        $this->objectHelper->tryCall($shipToTradeParty, "setURIUniversalCommunication", $universalCommunication);
        return $this;
    }

    /**
     * Add a contact to the Ship-To party.
     *
     * @param string|null $contactpersonname
     * Contact point for a legal entity, such as a personal name of the contact person
     * @param string|null $contactdepartmentname
     * Contact point for a legal entity, such as a name of the department or office
     * @param string|null $contactphoneno
     * Detailed information on the party's phone number
     * @param string|null $contactfaxno
     * Detailed information on the party's fax number
     * @param string|null $contactemailadd
     * Detailed information on the party's email address
     * @return OrderDocumentBuilder
     */
    public function addDocumentShipToContact(?string $contactpersonname = null, ?string $contactdepartmentname = null, ?string $contactphoneno = null, ?string $contactfaxno = null, ?string $contactemailadd = null, ?string $contactTypeCpde = null): OrderDocumentBuilder
    {
        $shipToTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeDelivery, "getShipToTradeParty");
        $contact = $this->objectHelper->getTradeContact($contactpersonname, $contactdepartmentname, $contactphoneno, $contactfaxno, $contactemailadd, $contactTypeCpde);
        $this->objectHelper->tryCall($shipToTradeParty, "addToDefinedTradeContact", $contact);
        return $this;
    }

    /**
     * Set detailed information of the deviating consignor party
     *
     * @param string $name
     * The name of the party
     * @param string|null $id
     * An identifier for the party. Multiple IDs can be assigned or specified. They can be differentiated by using
     * different identification schemes. If no scheme is given, it should  be known to the buyer and seller, e.g.
     * a previously exchanged identifier assigned by the buyer or seller.
     * @param string|null $description
     * Further legal information that is relevant for the party
     * @return OrderDocumentBuilder
     */
    public function setDocumentShipFrom(string $name, ?string $id = null, ?string $description = null): OrderDocumentBuilder
    {
        $shipToTradeParty = $this->objectHelper->getTradeParty($name, $id, $description);
        $this->objectHelper->tryCall($this->headerTradeDelivery, "setShipFromTradeParty", $shipToTradeParty);
        return $this;
    }

    /**
     * Add a global id for the deviating consignor party
     *
     * @param string $globalID
     * Global identifier of the goods recipient
     * @param string $globalIDType
     * Type of global identification number, must be selected from the entries in
     * the list published by the ISO / IEC 6523 Maintenance Agency.
     * @return OrderDocumentBuilder
     */
    public function addDocumentShipFromGlobalId(string $globalID, string $globalIDType): OrderDocumentBuilder
    {
        $shipFromTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeDelivery, "getShipFromTradeParty");
        $this->objectHelper->tryCall($shipFromTradeParty, "addToGlobalID", $this->objectHelper->getIdType($globalID, $globalIDType));
        return $this;
    }

    /**
     * Add Tax registration to the deviating consignor party
     *
     * @param string $taxregtype
     * Type of tax number of the party
     * @param string $taxregid
     * Tax number of the party or sales tax identification number of the (FC = Tax number, VA = Sales tax number)
     * @return OrderDocumentBuilder
     */
    public function addDocumentShipFromTaxRegistration(string $taxregtype, string $taxregid): OrderDocumentBuilder
    {
        $shipFromTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeDelivery, "getShipFromTradeParty");
        $taxreg = $this->objectHelper->getTaxRegistrationType($taxregtype, $taxregid);
        $this->objectHelper->tryCall($shipFromTradeParty, "addToSpecifiedTaxRegistration", $taxreg);
        return $this;
    }

    /**
     * Sets the postal address of the deviating consignor party
     *
     * @param string|null $lineone
     * The main line in the party's address. This is usually the street name and house number or
     * the post office box
     * @param string|null $linetwo
     * Line 2 of the party's address. This is an additional address line in an address that can be
     * used to provide additional details in addition to the main line
     * @param string|null $linethree
     * Line 3 of the party's address. This is an additional address line in an address that can be
     * used to provide additional details in addition to the main line
     * @param string|null $postcode
     * Identifier for a group of properties, such as a zip code
     * @param string|null $city
     * Usual name of the city or municipality in which the party's address is located
     * @param string|null $country
     * Code used to identify the country. If no tax agent is specified, this is the country in which the sales tax
     * is due. The lists of approved countries are maintained by the EN ISO 3166-1 Maintenance Agency “Codes for the
     * representation of names of countries and their subdivisions”
     * @param string|null $subdivision
     * The party's state
     * @return OrderDocumentBuilder
     */
    public function setDocumentShipFromAddress(?string $lineone = null, ?string $linetwo = null, ?string $linethree = null, ?string $postcode = null, ?string $city = null, ?string $country = null, ?string $subdivision = null): OrderDocumentBuilder
    {
        $shipFromTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeDelivery, "getShipFromTradeParty");
        $address = $this->objectHelper->getTradeAddress($lineone, $linetwo, $linethree, $postcode, $city, $country, $subdivision);
        $this->objectHelper->tryCall($shipFromTradeParty, "setPostalTradeAddress", $address);
        return $this;
    }

    /**
     * Set legal organisation of the deviating consignor party
     *
     * @param string|null $legalorgid
     * An identifier issued by an official registrar that identifies the
     * party as a legal entity or legal person. If no identification scheme ($legalorgtype) is provided,
     * it should be known to the buyer or seller party
     * @param string|null $legalorgtype
     * The identifier for the identification scheme of the legal registration of the party. In particular,
     * the following scheme codes are used: 0021 : SWIFT, 0088 : EAN, 0060 : DUNS, 0177 : ODETTE
     * @param string|null $legalorgname
     * A name by which the party is known, if different from the party's name (also known as the company name)
     * @return OrderDocumentBuilder
     */
    public function setDocumentShipFromLegalOrganisation(?string $legalorgid = null, ?string $legalorgtype = null, ?string $legalorgname = null): OrderDocumentBuilder
    {
        $shipFromTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeDelivery, "getShipFromTradeParty");
        $legalorg = $this->objectHelper->getLegalOrganization($legalorgid, $legalorgtype, $legalorgname);
        $this->objectHelper->tryCall($shipFromTradeParty, "setSpecifiedLegalOrganization", $legalorg);
        return $this;
    }

    /**
     * Set contact of the deviating consignor party
     *
     * @param string|null $contactpersonname
     * Contact point for a legal entity, such as a personal name of the contact person
     * @param string|null $contactdepartmentname
     * Contact point for a legal entity, such as a name of the department or office
     * @param string|null $contactphoneno
     * Detailed information on the party's phone number
     * @param string|null $contactfaxno
     * Detailed information on the party's fax number
     * @param string|null $contactemailadd
     * Detailed information on the party's email address
     * @param string|null $contactTypeCode
     * Contact type
     * @return OrderDocumentBuilder
     */
    public function setDocumentShipFromContact(?string $contactpersonname = null, ?string $contactdepartmentname = null, ?string $contactphoneno = null, ?string $contactfaxno = null, ?string $contactemailadd = null, ?string $contactTypeCode = null): OrderDocumentBuilder
    {
        $shipFromTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeDelivery, "getShipFromTradeParty");
        $contact = $this->objectHelper->getTradeContact($contactpersonname, $contactdepartmentname, $contactphoneno, $contactfaxno, $contactemailadd, $contactTypeCode);
        $this->objectHelper->tryCallIfMethodExists($shipFromTradeParty, "addToDefinedTradeContact", "setDefinedTradeContact", [$contact], $contact);
        return $this;
    }

    /**
     * Add an additional contact to the deviating consignor party.
     *
     * @param string|null $contactpersonname
     * Contact point for a legal entity, such as a personal name of the contact person
     * @param string|null $contactdepartmentname
     * Contact point for a legal entity, such as a name of the department or office
     * @param string|null $contactphoneno
     * Detailed information on the party's phone number
     * @param string|null $contactfaxno
     * Detailed information on the party's fax number
     * @param string|null $contactemailadd
     * Detailed information on the party's email address
     * @param string|null $contactTypeCode
     * Contact type
     * @return OrderDocumentBuilder
     */
    public function addDocumentShipFromContact(?string $contactpersonname = null, ?string $contactdepartmentname = null, ?string $contactphoneno = null, ?string $contactfaxno = null, ?string $contactemailadd = null, ?string $contactTypeCode = null): OrderDocumentBuilder
    {
        $shipFromTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeDelivery, "getShipFromTradeParty");
        $contact = $this->objectHelper->getTradeContact($contactpersonname, $contactdepartmentname, $contactphoneno, $contactfaxno, $contactemailadd, $contactTypeCode);
        $this->objectHelper->tryCall($shipFromTradeParty, "addToDefinedTradeContact", $contact);
        return $this;
    }

    /**
     * Set the universal communication info for the Buyer Requisitioner
     *
     * @param string|null $uriType
     * @param string|null $uriId
     * @return OrderDocumentBuilder
     */
    public function setDocumentShipFromUniversalCommunication(?string $uriType = null, ?string $uriId = null): OrderDocumentBuilder
    {
        $shipFromTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeDelivery, "getShipFromTradeParty");
        $universalCommunication = $this->objectHelper->getUniversalCommunicationType(null, $uriId, $uriType);
        $this->objectHelper->tryCall($shipFromTradeParty, "setURIUniversalCommunication", $universalCommunication);
        return $this;
    }

    /**
     * Set the requested date or period on which delivery is requested
     *
     * @param DateTime $occurrenceDateTime
     * @param DateTime|null $startDateTime
     * @param DateTime|null $endDateTime
     * @return OrderDocumentBuilder
     */
    public function setDocumentRequestedDeliverySupplyChainEvent(?DateTime $occurrenceDateTime = null, ?DateTime $startDateTime = null, ?DateTime $endDateTime = null): OrderDocumentBuilder
    {
        $supplychainevent = $this->objectHelper->getDeliverySupplyChainEvent($occurrenceDateTime, $startDateTime, $endDateTime);
        $this->objectHelper->tryCallIfMethodExists($this->headerTradeDelivery, "addToRequestedDeliverySupplyChainEvent", "setRequestedDeliverySupplyChainEvent", [$supplychainevent], $supplychainevent);
        return $this;
    }

    /**
     * Set detailed information on the Invoicee Trade Party
     *
     * @param string $name
     * The name of the party
     * @param string|null $id
     * An identifier for the party. Multiple IDs can be assigned or specified. They can be differentiated by using
     * different identification schemes. If no scheme is given, it should  be known to the buyer and seller, e.g.
     * a previously exchanged identifier assigned by the buyer or seller.
     * @param string|null $description
     * Further legal information that is relevant for the party
     * @return OrderDocumentBuilder
     */
    public function setDocumentInvoicee(string $name, ?string $id = null, ?string $description = null): OrderDocumentBuilder
    {
        $invoiceeTradeParty = $this->objectHelper->getTradeParty($name, $id, $description);
        $this->objectHelper->tryCall($this->headerTradeSettlement, "setInvoiceeTradeParty", $invoiceeTradeParty);
        return $this;
    }

    /**
     * Add a global id for the Invoicee Trade Party
     *
     * @param string $globalID
     * Global identification number
     * @param string $globalIDType
     * Type of global identification number, must be selected from the entries in
     * the list published by the ISO / IEC 6523 Maintenance Agency.
     * @return OrderDocumentBuilder
     */
    public function addDocumentInvoiceeGlobalId(string $globalID, string $globalIDType): OrderDocumentBuilder
    {
        $invoiceeTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeSettlement, "getInvoiceeTradeParty");
        $this->objectHelper->tryCall($invoiceeTradeParty, "addToGlobalID", $this->objectHelper->getIdType($globalID, $globalIDType));
        return $this;
    }

    /**
     * Add Tax registration to Invoicee Trade Party
     *
     * @param string $taxregtype
     * Type of tax number of the party
     * @param string $taxregid
     * Tax number of the party or sales tax identification number of the (FC = Tax number, VA = Sales tax number)
     * @return OrderDocumentBuilder
     */
    public function addDocumentInvoiceeTaxRegistration(string $taxregtype, string $taxregid): OrderDocumentBuilder
    {
        $invoiceeTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeSettlement, "getInvoiceeTradeParty");
        $taxreg = $this->objectHelper->getTaxRegistrationType($taxregtype, $taxregid);
        $this->objectHelper->tryCall($invoiceeTradeParty, "addToSpecifiedTaxRegistration", $taxreg);
        return $this;
    }

    /**
     * Sets the postal address of the Invoicee Trade Party
     *
     * @param string|null $lineone
     * The main line in the party's address. This is usually the street name and house number or
     * the post office box
     * @param string|null $linetwo
     * Line 2 of the party's address. This is an additional address line in an address that can be
     * used to provide additional details in addition to the main line
     * @param string|null $linethree
     * Line 3 of the party's address. This is an additional address line in an address that can be
     * used to provide additional details in addition to the main line
     * @param string|null $postcode
     * Identifier for a group of properties, such as a zip code
     * @param string|null $city
     * Usual name of the city or municipality in which the party's address is located
     * @param string|null $country
     * Code used to identify the country. If no tax agent is specified, this is the country in which the sales tax
     * is due. The lists of approved countries are maintained by the EN ISO 3166-1 Maintenance Agency “Codes for the
     * representation of names of countries and their subdivisions”
     * @param string|null $subdivision
     * The party's state
     * @return OrderDocumentBuilder
     */
    public function setDocumentInvoiceeAddress(?string $lineone = null, ?string $linetwo = null, ?string $linethree = null, ?string $postcode = null, ?string $city = null, ?string $country = null, ?string $subdivision = null): OrderDocumentBuilder
    {
        $invoiceeTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeSettlement, "getInvoiceeTradeParty");
        $address = $this->objectHelper->getTradeAddress($lineone, $linetwo, $linethree, $postcode, $city, $country, $subdivision);
        $this->objectHelper->tryCall($invoiceeTradeParty, "setPostalTradeAddress", $address);
        return $this;
    }

    /**
     * Set legal organisation of the Invoicee Trade Party
     *
     * @param string|null $legalorgid
     * An identifier issued by an official registrar that identifies the
     * party as a legal entity or legal person. If no identification scheme ($legalorgtype) is provided,
     * it should be known to the buyer or seller party
     * @param string|null $legalorgtype
     * The identifier for the identification scheme of the legal registration of the party. In particular,
     * the following scheme codes are used: 0021 : SWIFT, 0088 : EAN, 0060 : DUNS, 0177 : ODETTE
     * @param string|null $legalorgname
     * A name by which the party is known, if different from the party's name (also known as the company name)
     * @return OrderDocumentBuilder
     */
    public function setDocumentInvoiceeLegalOrganisation(?string $legalorgid = null, ?string $legalorgtype = null, ?string $legalorgname = null): OrderDocumentBuilder
    {
        $invoiceeTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeSettlement, "getInvoiceeTradeParty");
        $legalorg = $this->objectHelper->getLegalOrganization($legalorgid, $legalorgtype, $legalorgname);
        $this->objectHelper->tryCall($invoiceeTradeParty, "setSpecifiedLegalOrganization", $legalorg);
        return $this;
    }

    /**
     * Set contact of the Invoicee Trade Party
     *
     * @param string|null $contactpersonname
     * Contact point for a legal entity, such as a personal name of the contact person
     * @param string|null $contactdepartmentname
     * Contact point for a legal entity, such as a name of the department or office
     * @param string|null $contactphoneno
     * Detailed information on the party's phone number
     * @param string|null $contactfaxno
     * Detailed information on the party's fax number
     * @param string|null $contactemailadd
     * Detailed information on the party's email address
     * @return OrderDocumentBuilder
     */
    public function setDocumentInvoiceeContact(?string $contactpersonname = null, ?string $contactdepartmentname = null, ?string $contactphoneno = null, ?string $contactfaxno = null, ?string $contactemailadd = null, ?string $contactTypeCode = null): OrderDocumentBuilder
    {
        $invoiceeTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeSettlement, "getInvoiceeTradeParty");
        $contact = $this->objectHelper->getTradeContact($contactpersonname, $contactdepartmentname, $contactphoneno, $contactfaxno, $contactemailadd, $contactTypeCode);
        $this->objectHelper->tryCallIfMethodExists($invoiceeTradeParty, "addToDefinedTradeContact", "setDefinedTradeContact", [$contact], $contact);
        return $this;
    }

    /**
     * Add an additional contact to the ultimate Ship-from party
     *
     * @param string|null $contactpersonname
     * Contact point for a legal entity, such as a personal name of the contact person
     * @param string|null $contactdepartmentname
     * Contact point for a legal entity, such as a name of the department or office
     * @param string|null $contactphoneno
     * Detailed information on the party's phone number
     * @param string|null $contactfaxno
     * Detailed information on the party's fax number
     * @param string|null $contactemailadd
     * Detailed information on the party's email address
     * @return OrderDocumentBuilder
     */
    public function addDocumentInvoiceeContact(?string $contactpersonname, ?string $contactdepartmentname, ?string $contactphoneno, ?string $contactfaxno, ?string $contactemailadd, ?string $contactTypeCode): OrderDocumentBuilder
    {
        $invoiceeTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeSettlement, "getInvoiceeTradeParty");
        $contact = $this->objectHelper->getTradeContact($contactpersonname, $contactdepartmentname, $contactphoneno, $contactfaxno, $contactemailadd, $contactTypeCode);
        $this->objectHelper->tryCall($invoiceeTradeParty, "addToDefinedTradeContact", $contact);
        return $this;
    }

    /**
     * Set the universal communication info for the Invoicee
     *
     * @param string|null $uriType
     * @param string|null $uriId
     * @return OrderDocumentBuilder
     */
    public function setDocumentInvoiceeUniversalCommunication(?string $uriType = null, ?string $uriId = null): OrderDocumentBuilder
    {
        $invoiceeTradeParty = $this->objectHelper->tryCallAndReturn($this->headerTradeSettlement, "getInvoiceeTradeParty");
        $universalCommunication = $this->objectHelper->getUniversalCommunicationType(null, $uriId, $uriType);
        $this->objectHelper->tryCall($invoiceeTradeParty, "setURIUniversalCommunication", $universalCommunication);
        return $this;
    }

    /**
     * Set detailed information on the payment method
     *
     * __Notes__
     *  - The SpecifiedTradeSettlementPaymentMeans element can only be repeated for each bank account if
     *    several bank accounts are to be transferred for transfers. The code for the payment method in the Typecode
     *    element must therefore not differ in the repetitions. The elements ApplicableTradeSettlementFinancialCard
     *    and PayerPartyDebtorFinancialAccount must not be specified for bank transfers.
     *
     * @param string $paymentMeansCode
     * The expected or used means of payment, expressed as a code. The entries from the UNTDID 4461 code list
     * must be used. A distinction should be made between SEPA and non-SEPA payments as well as between credit
     * payments, direct debits, card payments and other means of payment In particular, the following codes can
     * be used:
     *  - 10: cash
     *  - 20: check
     *  - 30: transfer
     *  - 42: Payment to bank account
     *  - 48: Card payment
     *  - 49: direct debit
     *  - 57: Standing order
     *  - 58: SEPA Credit Transfer
     *  - 59: SEPA Direct Debit
     *  - 97: Report
     * @param string|null $paymentMeansInformation
     * The expected or used means of payment expressed in text form, e.g. cash, bank transfer, direct debit,
     * credit card, etc.
     * @return OrderDocumentBuilder
     */
    public function setDocumentPaymentMean(string $paymentMeansCode, ?string $paymentMeansInformation = null): OrderDocumentBuilder
    {
        $paymentMeans = $this->objectHelper->getTradeSettlementPaymentMeansType($paymentMeansCode, $paymentMeansInformation);
        $this->objectHelper->tryCall($this->headerTradeSettlement, "setSpecifiedTradeSettlementPaymentMeans", $paymentMeans);
        return $this;
    }

    /**
     * Add a payment term
     *
     * @param string $paymentTermsDescription
     * A text description of the payment terms that apply to the payment amount due (including a
     * description of possible penalties). Note: This element can contain multiple lines and
     * multiple conditions.
     * @return OrderDocumentBuilder
     */
    public function addDocumentPaymentTerm(string $paymentTermsDescription): OrderDocumentBuilder
    {
        if ($this->profile == OrderProfiles::PROFILE_EXTENDED) {
            $paymentTerms = $paymentTermsDescription;
        } else {
            $paymentTerms = $this->objectHelper->getTradePaymentTermsType($paymentTermsDescription);
        }
        $this->objectHelper->tryCallAll($this->headerTradeSettlement, ["addToSpecifiedTradePaymentTerms", "setSpecifiedTradePaymentTerms"], $paymentTerms);
        $this->currentPaymentTerms = $paymentTerms;
        return $this;
    }

    /**
     * Add information about surcharges and charges applicable to the bill as a whole, Deductions,
     * such as for withheld taxes may also be specified in this group
     *
     * @param float $actualAmount
     * Amount of the surcharge or discount at document level
     * @param boolean $isCharge
     * Switch that indicates whether the following data refer to an surcharge or a discount, true means that
     * this an charge
     * @param string|null $taxCategoryCode
     * A coded indication of which sales tax category applies to the surcharge or deduction at document level
     *
     * The following entries from UNTDID 5305 are used (details in brackets):
     *  - Standard rate (sales tax is due according to the normal procedure)
     *  - Goods to be taxed according to the zero rate (sales tax is charged with a percentage of zero)
     *  - Tax exempt (USt./IGIC/IPSI)
     *  - Reversal of the tax liability (the rules for reversing the tax liability at USt./IGIC/IPSI apply)
     *  - VAT exempt for intra-community deliveries of goods (USt./IGIC/IPSI not levied due to rules on intra-community deliveries)
     *  - Free export item, tax not levied (VAT / IGIC/IPSI not levied due to export outside the EU)
     *  - Services outside the tax scope (sales are not subject to VAT / IGIC/IPSI)
     *  - Canary Islands general indirect tax (IGIC tax applies)
     *  - IPSI (tax for Ceuta / Melilla) applies.
     *
     * The codes for the VAT category are as follows:
     *  - S = sales tax is due at the normal rate
     *  - Z = goods to be taxed according to the zero rate
     *  - E = tax exempt
     *  - AE = reversal of tax liability
     *  - K = VAT is not shown for intra-community deliveries
     *  - G = tax not levied due to export outside the EU
     *  - O = Outside the tax scope
     *  - L = IGIC (Canary Islands)
     *  - M = IPSI (Ceuta/Melilla)
     * @param string|null $taxTypeCode
     * Code for the VAT category of the surcharge or charge at document level. Note: Fixed value = "VAT"
     * @param float|null $rateApplicablePercent
     * VAT rate for the surcharge or discount on document level. Note: The code of the sales tax category
     * and the category-specific sales tax rate must correspond to one another. The value to be given is
     * the percentage. For example, the value 20 is given for 20% (and not 0.2)
     * @param float|null $sequence
     * Calculation order
     * @param float|null $calculationPercent
     * Percentage surcharge or discount at document level
     * @param float|null $basisAmount
     * The base amount that may be used in conjunction with the percentage of the surcharge or discount
     * at document level to calculate the amount of the discount at document level
     * @param float|null $basisQuantity
     * Basismenge des Rabatts
     * @param string|null $basisQuantityUnitCode
     * Einheit der Preisbasismenge
     *  - Codeliste: Rec. N°20 Vollständige Liste, In Recommendation N°20 Intro 2.a ist beschrieben, dass
     *    beide Listen kombiniert anzuwenden sind.
     *  - Codeliste: Rec. N°21 Vollständige Liste, In Recommendation N°20 Intro 2.a ist beschrieben, dass
     *    beide Listen kombiniert anzuwenden sind.
     * @param string|null $reasonCode
     * The reason given as a code for the surcharge or discount at document level. Note: Use entries from
     * the UNTDID 5189 code list. The code of the reason for the surcharge or discount at document level
     * and the reason for the surcharge or discount at document level must correspond to each other
     *
     * Code list: UNTDID 7161 Complete list, code list: UNTDID 5189 Restricted
     * Include PEPPOL subset:
     *  - 41 - Bonus for works ahead of schedule
     *  - 42 - Other bonus
     *  - 60 - Manufacturer’s consumer discount
     *  - 62 - Due to military status
     *  - 63 - Due to work accident
     *  - 64 - Special agreement
     *  - 65 - Production error discount
     *  - 66 - New outlet discount
     *  - 67 - Sample discount
     *  - 68 - End-of-range discount
     *  - 70 - Incoterm discount
     *  - 71 - Point of sales threshold allowance
     *  - 88 - Material surcharge/deduction
     *  - 95 - Discount
     *  - 100 - Special rebate
     *  - 102 - Fixed long term
     *  - 103 - Temporary
     *  - 104 - Standard
     *  - 105 - Yearly turnover
     * @param string|null $reason
     * The reason given in text form for the surcharge or discount at document level
     * @return OrderDocumentBuilder
     */
    public function addDocumentAllowanceCharge(float $actualAmount, bool $isCharge, ?string $taxCategoryCode = null, ?string $taxTypeCode = null, ?float $rateApplicablePercent = null, ?float $sequence = null, ?float $calculationPercent = null, ?float $basisAmount = null, ?float $basisQuantity = null, ?string $basisQuantityUnitCode = null, ?string $reasonCode = null, ?string $reason = null): OrderDocumentBuilder
    {
        $allowanceCharge = $this->objectHelper->getTradeAllowanceChargeType($actualAmount, $isCharge, $taxTypeCode, $taxCategoryCode, $rateApplicablePercent, $sequence, $calculationPercent, $basisAmount, $basisQuantity, $basisQuantityUnitCode, $reasonCode, $reason);
        $this->objectHelper->tryCall($this->headerTradeSettlement, "addToSpecifiedTradeAllowanceCharge", $allowanceCharge);
        return $this;
    }

    /**
     * Set an AccountingAccount
     * Detailinformationen zur Buchungsreferenz
     *
     * @param string $id
     * @param string|null $typeCode
     * @return OrderDocumentBuilder
     */
    public function setDocumentReceivableSpecifiedTradeAccountingAccount(string $id, ?string $typeCode = null): OrderDocumentBuilder
    {
        $account = $this->objectHelper->getTradeAccountingAccountType($id, $typeCode);
        $this->objectHelper->tryCall($this->headerTradeSettlement, "setReceivableSpecifiedTradeAccountingAccount", $account);
        return $this;
    }

    /**
     * Adds a new position (line) to document
     *
     * @param string $lineid
     * A unique identifier for the relevant item within the invoice (item number)
     * @param string|null $lineStatusCode
     * Indicates whether the invoice item contains prices that must be taken into account when
     * calculating the invoice amount, or whether it only contains information.
     * The following code should be used: TYPE_LINE
     * @return OrderDocumentBuilder
     */
    public function addNewPosition(string $lineid, ?string $lineStatusCode = null): OrderDocumentBuilder
    {
        $position = $this->objectHelper->getSupplyChainTradeLineItemType($lineid, $lineStatusCode);
        $this->objectHelper->tryCall($this->headerSupplyChainTradeTransaction, "addToIncludedSupplyChainTradeLineItem", $position);
        $this->currentPosition = $position;
        return $this;
    }

    /**
     * Add detailed information on the free text on the position
     *
     * @param string|null $content
     * A free text that contains unstructured information that is relevant to the invoice item
     * @param string|null $contentCode
     * Text modules agreed bilaterally, which are transmitted here as code.
     * @param string|null $subjectCode
     * Free text for the position (code for the type)
     * __Codelist:__ UNTDID 4451
     * @return OrderDocumentBuilder
     */
    public function setDocumentPositionNote(?string $content = null, ?string $contentCode = null, ?string $subjectCode = null): OrderDocumentBuilder
    {
        $linedoc = $this->objectHelper->tryCallAndReturn($this->currentPosition, "getAssociatedDocumentLineDocument");
        $note = $this->objectHelper->getNoteType($content, $contentCode, $subjectCode);
        $this->objectHelper->tryCallAll($linedoc, ["addToIncludedNote", "setIncludedNote"], $note);
        return $this;
    }

    /**
     * Adds product details to the last created position (line) in the document
     *
     * @param string|null $name
     * A name of the item (item name)
     * @param string|null $description
     * A description of the item, the item description makes it possible to describe the item and its
     * properties in more detail than is possible with the item name.
     * @param string|null $sellerAssignedID
     * An identifier assigned to the item by the seller
     * @param string|null $buyerAssignedID
     * An identifier assigned to the item by the buyer. The article number of the buyer is a clear,
     * bilaterally agreed identification of the product. It can, for example, be the customer article
     * number or the article number assigned by the manufacturer.
     * @param string|null $globalIDType
     * The scheme for $globalID
     * @param string|null $globalID
     * Identification of an article according to the registered scheme (Global identifier of the product,
     * GTIN, ...)
     * @return OrderDocumentBuilder
     */
    public function setDocumentPositionProductDetails(?string $name = null, ?string $description = null, ?string $sellerAssignedID = null, ?string $buyerAssignedID = null, ?string $globalIDType = null, ?string $globalID = null, ?string $batchId = null, ?string $brandName = null): OrderDocumentBuilder
    {
        $product = $this->objectHelper->getTradeProductType($name, $description, $sellerAssignedID, $buyerAssignedID, $globalIDType, $globalID, $batchId, $brandName);
        $this->objectHelper->tryCall($this->currentPosition, "setSpecifiedTradeProduct", $product);
        return $this;
    }

    /**
     * Set (single) extra characteristics to the formerly added product.
     * Contains information about the characteristics of the goods and services invoiced
     *
     * @param string $description
     * The name of the attribute or property of the product such as "Colour"
     * @param string $value
     * The value of the attribute or property of the product such as "Red"
     * @param string|null $typecode
     * Type of product property (code). The codes must be taken from the
     * UNTDID 6313 codelist. Available only in the Extended-Profile
     * @return OrderDocumentBuilder
     */
    public function setDocumentPositionProductCharacteristic(string $description, string $value, ?string $typecode = null): OrderDocumentBuilder
    {
        $product = $this->objectHelper->tryCallAndReturn($this->currentPosition, "getSpecifiedTradeProduct");
        $productCharacteristic = $this->objectHelper->getProductCharacteristicType($typecode, $description, $value);
        $this->objectHelper->tryCallIfMethodExists($product, "addToApplicableProductCharacteristic", "setApplicableProductCharacteristic", [$productCharacteristic], $productCharacteristic);
        return $this;
    }

    /**
     * Add extra characteristics to the formerly added product.
     * Contains information about the characteristics of the goods and services invoiced
     *
     * @param string $description
     * The name of the attribute or property of the product such as "Colour"
     * @param string $value
     * The value of the attribute or property of the product such as "Red"
     * @param string|null $typecode
     * Type of product property (code). The codes must be taken from the
     * UNTDID 6313 codelist. Available only in the Extended-Profile
     * @return OrderDocumentBuilder
     */
    public function addDocumentPositionProductCharacteristic(string $description, string $value, ?string $typecode = null): OrderDocumentBuilder
    {
        $product = $this->objectHelper->tryCallAndReturn($this->currentPosition, "getSpecifiedTradeProduct");
        $productCharacteristic = $this->objectHelper->getProductCharacteristicType($typecode, $description, $value);
        $this->objectHelper->tryCall($product, "addToApplicableProductCharacteristic", $productCharacteristic);
        return $this;
    }

    /**
     * Set (single) detailed information on product classification
     *
     * @param string $classCode
     * A code for classifying the item by type or nature or essence or condition.
     * __Note__: Classification codes are used to group similar items for different purposes, such as public
     * procurement (using the Common Procurement Vocabulary [CPV]), e-commerce (UNSPSC), etc.
     * @param string|null $className
     * Classification name
     * @param string|null $listID
     * The identifier for the identification scheme of the identifier of the article classification
     * __Note__: The identification scheme must be selected from the entries from UNTDID 7143.
     * @param string|null $listVersionID
     * The version of the identification scheme
     * @return OrderDocumentBuilder
     */
    public function setDocumentPositionProductClassification(string $classCode, ?string $className = null, ?string $listID = null, ?string $listVersionID = null): OrderDocumentBuilder
    {
        $product = $this->objectHelper->tryCallAndReturn($this->currentPosition, "getSpecifiedTradeProduct");
        $productClassification = $this->objectHelper->getProductClassificationType($classCode, $className, $listID, $listVersionID);
        $this->objectHelper->tryCallIfMethodExists($product, "addToDesignatedProductClassification", "setDesignatedProductClassification", [$productClassification], $productClassification);
        return $this;
    }

    /**
     * Add detailed information on product classification
     *
     * @param string $classCode
     * A code for classifying the item by type or nature or essence or condition.
     * __Note__: Classification codes are used to group similar items for different purposes, such as public
     * procurement (using the Common Procurement Vocabulary [CPV]), e-commerce (UNSPSC), etc.
     * @param string|null $className
     * Classification name
     * @param string|null $listID
     * The identifier for the identification scheme of the identifier of the article classification
     * __Note__: The identification scheme must be selected from the entries from UNTDID 7143.
     * @param string|null $listVersionID
     * The version of the identification scheme
     * @return OrderDocumentBuilder
     */
    public function addDocumentPositionProductClassification(string $classCode, ?string $className = null, ?string $listID = null, ?string $listVersionID = null): OrderDocumentBuilder
    {
        $product = $this->objectHelper->tryCallAndReturn($this->currentPosition, "getSpecifiedTradeProduct");
        $productClassification = $this->objectHelper->getProductClassificationType($classCode, $className, $listID, $listVersionID);
        $this->objectHelper->tryCall($product, "addToDesignatedProductClassification", $productClassification);
        return $this;
    }

    /**
     * Set the unique batch identifier for this trade product instance and
     * the unique supplier assigned serial identifier for this trade product instance.
     *
     * @param string $batchID
     * The unique batch identifier for this trade product instance
     * @param string|null $serialId
     * The unique supplier assigned serial identifier for this trade product instance.
     * @return OrderDocumentBuilder
     */
    public function setDocumentPositionProductInstance(?string $batchID = null, ?string $serialId = null): OrderDocumentBuilder
    {
        $product = $this->objectHelper->tryCallAndReturn($this->currentPosition, "getSpecifiedTradeProduct");
        $productInstance = $this->objectHelper->getTradeProductInstanceType($batchID, $serialId);
        $this->objectHelper->tryCallIfMethodExists($product, "addToIndividualTradeProductInstance", "setIndividualTradeProductInstance", [$productInstance], $productInstance);
        return $this;
    }

    /**
     * Add a new unique batch identifier for this trade product instance and
     * the unique supplier assigned serial identifier for this trade product instance.
     *
     * @param string $batchID
     * The unique batch identifier for this trade product instance
     * @param string|null $serialId
     * The unique supplier assigned serial identifier for this trade product instance.
     * @return OrderDocumentBuilder
     */
    public function addDocumentPositionProductInstance(?string $batchID = null, ?string $serialId = null): OrderDocumentBuilder
    {
        $product = $this->objectHelper->tryCallAndReturn($this->currentPosition, "getSpecifiedTradeProduct");
        $productInstance = $this->objectHelper->getTradeProductInstanceType($batchID, $serialId);
        $this->objectHelper->tryCall($product, "addToIndividualTradeProductInstance", $productInstance);
        return $this;
    }

    /**
     * Specify the supply chain packaging
     *
     * @param string|null $typeCode
     * The code specifying the type of supply chain packaging.
     * @param float|null $width
     * The measure of the width component of this spatial dimension.
     * @param string|null $widthUnitCode
     * Unit Code of the measure of the width component of this spatial dimension.
     * @param float|null $length
     * The measure of the length component of this spatial dimension.
     * @param string|null $lengthUnitCode
     * Unit Code of the measure of the Length component of this spatial dimension.
     * @param float|null $height
     * The measure of the height component of this spatial dimension.
     * @param string|null $heightUnitCode
     * Unit Code of the measure of the Height component of this spatial dimension.
     * @return OrderDocumentBuilder
     */
    public function setDocumentPositionApplicableSupplyChainPackaging(?string $typeCode = null, ?float $width = null, ?string $widthUnitCode = null, ?float $length = null, ?string $lengthUnitCode = null, ?float $height = null, ?string $heightUnitCode = null): OrderDocumentBuilder
    {
        $product = $this->objectHelper->tryCallAndReturn($this->currentPosition, "getSpecifiedTradeProduct");
        $packaging = $this->objectHelper->getSupplyChainPackagingType($typeCode, $width, $widthUnitCode, $length, $lengthUnitCode, $height, $heightUnitCode);
        $this->objectHelper->tryCall($product, "setApplicableSupplyChainPackaging", $packaging);
        return $this;
    }

    /**
     * Sets the detailed information on the product origin
     *
     * @param string $country
     * The code indicating the country the goods came from
     * __Note__: The lists of approved countries are maintained by the EN ISO 3166-1 Maintenance
     * Agency “Codes for the representation of names of countries and their subdivisions”.
     * @return OrderDocumentBuilder
     */
    public function setDocumentPositionProductOriginTradeCountry(string $country): OrderDocumentBuilder
    {
        $product = $this->objectHelper->tryCallAndReturn($this->currentPosition, "getSpecifiedTradeProduct");
        $productTradeCounty = $this->objectHelper->getTradeCountryType($country);
        $this->objectHelper->tryCall($product, "setOriginTradeCountry", $productTradeCounty);
        return $this;
    }

    /**
     * Undocumented function
     *
     * @param string|null $issuerassignedid
     * @param string|null $typecode
     * @param string|null $uriid
     * @param string|null $lineid
     * @param string|null $name
     * @param string|null $reftypecode
     * @param DateTime|null $issueddate
     * @param string|null $binarydatafilename
     * @return OrderDocumentBuilder
     */
    public function setDocumentPositionProductReferencedDocument(?string $issuerassignedid = null, ?string $typecode = null, ?string $uriid = null, ?string $lineid = null, ?string $name = null, ?string $reftypecode = null, ?DateTime $issueddate = null, ?string $binarydatafilename = null): OrderDocumentBuilder
    {
        $product = $this->objectHelper->tryCallAndReturn($this->currentPosition, "getSpecifiedTradeProduct");
        $refdoc = $this->objectHelper->getReferencedDocumentType($issuerassignedid, $uriid, $lineid, $typecode, $name, $reftypecode, $issueddate, $binarydatafilename);
        $this->objectHelper->tryCallIfMethodExists($product, "addToAdditionalReferenceReferencedDocument", "setAdditionalReferenceReferencedDocument", [$refdoc], $refdoc);
        return $this;
    }

    /**
     * Undocumented function
     *
     * @param string|null $issuerassignedid
     * @param string|null $typecode
     * @param string|null $uriid
     * @param string|null $lineid
     * @param string|null $name
     * @param string|null $reftypecode
     * @param DateTime|null $issueddate
     * @param string|null $binarydatafilename
     * @return OrderDocumentBuilder
     */
    public function addDocumentPositionProductReferencedDocument(?string $issuerassignedid = null, ?string $typecode = null, ?string $uriid = null, ?string $lineid = null, ?string $name = null, ?string $reftypecode = null, ?DateTime $issueddate = null, ?string $binarydatafilename = null): OrderDocumentBuilder
    {
        $product = $this->objectHelper->tryCallAndReturn($this->currentPosition, "getSpecifiedTradeProduct");
        $contractrefdoc = $this->objectHelper->getReferencedDocumentType($issuerassignedid, $uriid, $lineid, $typecode, $name, $reftypecode, $issueddate, $binarydatafilename);
        $this->objectHelper->tryCall($product, "addToAdditionalReferenceReferencedDocument", $contractrefdoc);
        return $this;
    }

    /**
     * Add an additional Document reference on a position
     *
     * @param string|null $issuerassignedid
     * The identifier of the tender or lot to which the invoice relates, or an identifier specified by the seller for
     * an object on which the invoice is based, or an identifier of the document on which the invoice is based.
     * @param string|null $typecode
     * Type of referenced document (See codelist UNTDID 1001)
     *  - Code 916 "reference paper" is used to reference the identification of the document on which the invoice is based
     *  - Code 50 "Price / sales catalog response" is used to reference the tender or the lot
     *  - Code 130 "invoice data sheet" is used to reference an identifier for an object specified by the seller.
     * @param string|null $uriid
     * The Uniform Resource Locator (URL) at which the external document is available. A means of finding the resource
     * including the primary access method intended for it, e.g. http: // or ftp: //. The location of the external document
     * must be used if the buyer needs additional information to support the amounts billed. External documents are not part
     * of the invoice. Access to external documents can involve certain risks.
     * @param string|null $lineid
     * The referenced position identifier in the additional document
     * @param string|null $name
     * A description of the document, e.g. Hourly billing, usage or consumption report, etc.
     * @param string|null $reftypecode
     * The identifier for the identification scheme of the identifier of the item invoiced. If it is not clear to the
     * recipient which scheme is used for the identifier, an identifier of the scheme should be used, which must be selected
     * from UNTDID 1153 in accordance with the code list entries.
     * @param DateTime|null $issueddate
     * Document date
     * @param string|null $binarydatafilename
     * Contains a file name of an attachment document embedded as a binary object
     * @return OrderDocumentBuilder
     */
    public function addDocumentPositionAdditionalReferencedDocument(?string $issuerassignedid = null, ?string $typecode = null, ?string $uriid = null, ?string $lineid = null, ?string $name = null, ?string $reftypecode = null, ?DateTime $issueddate = null, ?string $binarydatafilename = null): OrderDocumentBuilder
    {
        $contractrefdoc = $this->objectHelper->getReferencedDocumentType($issuerassignedid, $uriid, $lineid, $typecode, $name, $reftypecode, $issueddate, $binarydatafilename);
        $positionAgreement = $this->objectHelper->tryCallAndReturn($this->currentPosition, "getSpecifiedLineTradeAgreement");
        $this->objectHelper->tryCall($positionAgreement, "addToAdditionalReferencedDocument", $contractrefdoc);
        return $this;
    }

    /**
     * Set details of the related buyer order position
     *
     * @param string $buyerOrderRefLineId
     * An identifier for a position within an order placed by the buyer. Note: Reference is made to the order
     * reference at the document level.
     * @return OrderDocumentBuilder
     */
    public function setDocumentPositionBuyerOrderReferencedDocument(string $buyerOrderRefLineId): OrderDocumentBuilder
    {
        $buyerorderrefdoc = $this->objectHelper->getReferencedDocumentType(null, null, $buyerOrderRefLineId, null, null, null, null, null);
        $positionAgreement = $this->objectHelper->tryCallAndReturn($this->currentPosition, "getSpecifiedLineTradeAgreement");
        $this->objectHelper->tryCall($positionAgreement, "setBuyerOrderReferencedDocument", $buyerorderrefdoc);
        return $this;
    }

    /**
     * Set the reference of quotation document
     *
     * @param string|null $quotationRefId
     * The quotation document referenced in this line trade agreement
     * @param string|null $quotationRefLineId
     * The unique identifier of a line in this Quotation referenced document.
     * @return OrderDocumentBuilder
     */
    public function setDocumentPositionQuotationReferencedDocument(?string $quotationRefId = null, ?string $quotationRefLineId = null): OrderDocumentBuilder
    {
        $quotationRefDoc = $this->objectHelper->getReferencedDocumentType($quotationRefId, null, $quotationRefLineId, null, null, null, null, null);
        $positionAgreement = $this->objectHelper->tryCallAndReturn($this->currentPosition, "getSpecifiedLineTradeAgreement");
        $this->objectHelper->tryCall($positionAgreement, "setQuotationReferencedDocument", $quotationRefDoc);
        return $this;
    }

    /**
     * Set the unit price excluding sales tax before deduction of the discount on the item price.
     *
     * @param float $chargeAmount
     * The unit price excluding sales tax before deduction of the discount on the item price.
     * Note: If the price is shown according to the net calculation, the price must also be shown
     * according to the gross calculation.
     * @param float|null $basisQuantity
     * The number of item units for which the price applies (price base quantity)
     * @param string|null $basisQuantityUnitCode
     * The unit code of the number of item units for which the price applies (price base quantity)
     * @return OrderDocumentBuilder
     */
    public function setDocumentPositionGrossPrice(float $chargeAmount, ?float $basisQuantity = null, ?string $basisQuantityUnitCode = null): OrderDocumentBuilder
    {
        $grossPrice = $this->objectHelper->getTradePriceType($chargeAmount, $basisQuantity, $basisQuantityUnitCode);
        $positionAgreement = $this->objectHelper->tryCallAndReturn($this->currentPosition, "getSpecifiedLineTradeAgreement");
        $this->objectHelper->tryCall($positionAgreement, "setGrossPriceProductTradePrice", $grossPrice);
        return $this;
    }

    /**
     * Detailed information on surcharges and discounts on item gross price
     *
     * @param float $actualAmount
     * Discount on the item price. The total discount subtracted from the gross price to calculate the
     * net price. Note: Only applies if the discount is given per unit and is not included in the gross price.
     * @param boolean $isCharge
     * Switch for surcharge/discount, if true then its an charge
     * @param string|null $reason
     * The reason for the order line item trade price charge expressed as text.
     * @param string|null $reasonCode
     * The reason for the order line item trade price charge, expressed as a code.
     * Use entries of the UNTDID 7161 code list . The order line level item trade price discount reason code
     * and the order line level item trade price discount reason shall indicate the same item trade price
     * charge reason. Example AEW for WEEE.
     * @return OrderDocumentBuilder
     */
    public function addDocumentPositionGrossPriceAllowanceCharge(float $actualAmount, bool $isCharge, ?string $reason = null, ?string $reasonCode = null): OrderDocumentBuilder
    {
        $positionAgreement = $this->objectHelper->tryCallAndReturn($this->currentPosition, "getSpecifiedLineTradeAgreement");
        $grossPrice = $this->objectHelper->tryCallAndReturn($positionAgreement, "getGrossPriceProductTradePrice");
        $allowanceCharge = $this->objectHelper->getTradeAllowanceChargeType($actualAmount, $isCharge, null, null, null, null, null, null, null, null, $reasonCode, $reason);
        $this->objectHelper->tryCallAll($grossPrice, ["addToAppliedTradeAllowanceCharge", "setAppliedTradeAllowanceCharge"], $allowanceCharge);
        return $this;
    }

    /**
     * Set detailed information on the net price of the item
     *
     * @param float $chargeAmount
     * Net price of the item
     * @param float|null $basisQuantity
     * Base quantity at the item price
     * @param string|null $basisQuantityUnitCode
     * Code of the unit of measurement of the base quantity at the item price
     * @return OrderDocumentBuilder
     */
    public function setDocumentPositionNetPrice(float $chargeAmount, ?float $basisQuantity = null, ?string $basisQuantityUnitCode = null): OrderDocumentBuilder
    {
        $netPrice = $this->objectHelper->getTradePriceType($chargeAmount, $basisQuantity, $basisQuantityUnitCode);
        $positionAgreement = $this->objectHelper->tryCallAndReturn($this->currentPosition, "getSpecifiedLineTradeAgreement");
        $this->objectHelper->tryCall($positionAgreement, "setNetPriceProductTradePrice", $netPrice);
        return $this;
    }

    /**
     * Set the Referenced Catalog ID applied to this line
     *
     * @param string|null $catalogueRefId
     * @param string|null $catalogueRefLineId
     * @return OrderDocumentBuilder
     */
    public function setDocumentPositionCatalogueReferencedDocument(?string $catalogueRefId = null, ?string $catalogueRefLineId = null): OrderDocumentBuilder
    {
        $quotationrefdoc = $this->objectHelper->getReferencedDocumentType($catalogueRefId, null, $catalogueRefLineId, null, null, null, null, null);
        $positionAgreement = $this->objectHelper->tryCallAndReturn($this->currentPosition, "getSpecifiedLineTradeAgreement");
        $this->objectHelper->tryCallIfMethodExists($positionAgreement, "addToCatalogueReferencedDocument", "setCatalogueReferencedDocument", [$quotationrefdoc], $quotationrefdoc);
        return $this;
    }

    /**
     * Set the Referenced Catalog ID applied to this line
     *
     * @param string|null $catalogueRefId
     * @param string|null $catalogueRefLineId
     * @return OrderDocumentBuilder
     */
    public function addDocumentPositionCatalogueReferencedDocument(?string $catalogueRefId = null, ?string $catalogueRefLineId = null): OrderDocumentBuilder
    {
        $quotationrefdoc = $this->objectHelper->getReferencedDocumentType($catalogueRefId, null, $catalogueRefLineId, null, null, null, null, null);
        $positionAgreement = $this->objectHelper->tryCallAndReturn($this->currentPosition, "getSpecifiedLineTradeAgreement");
        $this->objectHelper->tryCall($positionAgreement, "addToCatalogueReferencedDocument", $quotationrefdoc);
        return $this;
    }

    /**
     * Set details of a blanket order referenced document on position-level
     *
     * @param string $blanketOrderRefLineId
     * The unique identifier of a line in the Blanketl Order referenced document
     * @return OrderDocumentBuilder
     */
    public function setDocumentPositionBlanketOrderReferencedDocument(string $blanketOrderRefLineId): OrderDocumentBuilder
    {
        $blanketOrderRefDoc = $this->objectHelper->getReferencedDocumentType(null, null, $blanketOrderRefLineId, null, null, null, null);
        $positionAgreement = $this->objectHelper->tryCallAndReturn($this->currentPosition, "getSpecifiedLineTradeAgreement");
        $this->objectHelper->tryCall($positionAgreement, "setBlanketOrderReferencedDocument", $blanketOrderRefDoc);
        return $this;
    }

    /**
     * The indication, at line level, of whether or not this trade delivery can be partially delivered.
     *
     * @param boolean $partialDelivery
     * If TRUE partial delivery is allowed
     * @return OrderDocumentBuilder
     */
    public function setDocumentPositionPartialDelivery(bool $partialDelivery = false): OrderDocumentBuilder
    {
        $indicator = $this->objectHelper->getIndicatorType($partialDelivery);
        $positionDelivery = $this->objectHelper->tryCallAndReturn($this->currentPosition, "getSpecifiedLineTradeDelivery");
        $this->objectHelper->tryCall($positionDelivery, "setPartialDeliveryAllowedIndicator", $indicator);
        return $this;
    }

    /**
     * Set the quantity, at line level, requested for this trade delivery.
     *
     * @param float $requestedQuantity
     * The quantity, at line level, requested for this trade delivery.
     * @param string $requestedQuantityUnitCode
     * Unit Code for the requested quantity.
     * @return OrderDocumentBuilder
     */
    public function setDocumentPositionDeliverReqQuantity(float $requestedQuantity, string $requestedQuantityUnitCode): OrderDocumentBuilder
    {
        $quantity = $this->objectHelper->getQuantityType($requestedQuantity, $requestedQuantityUnitCode);
        $positionDelivery = $this->objectHelper->tryCallAndReturn($this->currentPosition, "getSpecifiedLineTradeDelivery");
        $this->objectHelper->tryCall($positionDelivery, "setRequestedQuantity", $quantity);
        return $this;
    }

    /**
     * Set the number of packages, at line level, in this trade delivery.
     *
     * @param float $packageQuantity
     * The number of packages, at line level, in this trade delivery.
     * @param string $packageQuantityUnitCode
     * Unit Code for the package quantity.
     * @return OrderDocumentBuilder
     */
    public function setDocumentPositionDeliverPackageQuantity(float $packageQuantity, string $packageQuantityUnitCode): OrderDocumentBuilder
    {
        $quantity = $this->objectHelper->getQuantityType($packageQuantity, $packageQuantityUnitCode);
        $positionDelivery = $this->objectHelper->tryCallAndReturn($this->currentPosition, "getSpecifiedLineTradeDelivery");
        $this->objectHelper->tryCall($positionDelivery, "setPackageQuantity", $quantity);
        return $this;
    }

    /**
     * Set the number of packages, at line level, in this trade delivery.
     *
     * @param float $perPackageQuantity
     * The number of packages, at line level, in this trade delivery.
     * @param string $perPackageQuantityUnitCode
     * Unit Code for the package quantity.
     * @return OrderDocumentBuilder
     */
    public function setDocumentPositionDeliverPerPackageQuantity(float $perPackageQuantity, string $perPackageQuantityUnitCode): OrderDocumentBuilder
    {
        $quantity = $this->objectHelper->getQuantityType($perPackageQuantity, $perPackageQuantityUnitCode);
        $positionDelivery = $this->objectHelper->tryCallAndReturn($this->currentPosition, "getSpecifiedLineTradeDelivery");
        $this->objectHelper->tryCall($positionDelivery, "setPerPackageUnitQuantity", $quantity);
        return $this;
    }

    /**
     * Set the quantity, at line level, agreed for this trade delivery.
     *
     * @param float $agreedQuantity
     * The quantity, at line level, agreed for this trade delivery.
     * @param string $agreedQuantityUnitCode
     * Unit Code for the package quantity.
     * @return OrderDocumentBuilder
     */
    public function setDocumentPositionDeliverAgreedQuantity(float $agreedQuantity, string $agreedQuantityUnitCode): OrderDocumentBuilder
    {
        $quantity = $this->objectHelper->getQuantityType($agreedQuantity, $agreedQuantityUnitCode);
        $positionDelivery = $this->objectHelper->tryCallAndReturn($this->currentPosition, "getSpecifiedLineTradeDelivery");
        $this->objectHelper->tryCall($positionDelivery, "setAgreedQuantity", $quantity);
        return $this;
    }

    /**
     * Supply chain event on position level
     *
     * @param DateTime|null $occurrenceDateTime
     * @param DateTime|null $startDateTime
     * @param DateTime|null $endDateTime
     * @return OrderDocumentBuilder
     */
    public function addDocumentPositionRequestedDeliverySupplyChainEvent(?DateTime $occurrenceDateTime = null, ?DateTime $startDateTime = null, ?DateTime $endDateTime = null): OrderDocumentBuilder
    {
        $supplychainevent = $this->objectHelper->getDeliverySupplyChainEvent($occurrenceDateTime, $startDateTime, $endDateTime);
        $positionDelivery = $this->objectHelper->tryCallAndReturn($this->currentPosition, "getSpecifiedLineTradeDelivery");
        $this->objectHelper->tryCall($positionDelivery, "addToRequestedDeliverySupplyChainEvent", $supplychainevent);
        return $this;
    }

    /**
     * Add information about the sales tax that applies to the goods and services invoiced
     * in the relevant invoice line
     *
     * @param string $categoryCode
     * Coded description of a sales tax category
     *
     * The following entries from UNTDID 5305 are used (details in brackets):
     *  - Standard rate (sales tax is due according to the normal procedure)
     *  - Goods to be taxed according to the zero rate (sales tax is charged with a percentage of zero)
     *  - Tax exempt (USt./IGIC/IPSI)
     *  - Reversal of the tax liability (the rules for reversing the tax liability at USt./IGIC/IPSI apply)
     *  - VAT exempt for intra-community deliveries of goods (USt./IGIC/IPSI not levied due to rules on intra-community deliveries)
     *  - Free export item, tax not levied (VAT / IGIC/IPSI not levied due to export outside the EU)
     *  - Services outside the tax scope (sales are not subject to VAT / IGIC/IPSI)
     *  - Canary Islands general indirect tax (IGIC tax applies)
     *  - IPSI (tax for Ceuta / Melilla) applies.
     *
     * The codes for the VAT category are as follows:
     *  - S = sales tax is due at the normal rate
     *  - Z = goods to be taxed according to the zero rate
     *  - E = tax exempt
     *  - AE = reversal of tax liability
     *  - K = VAT is not shown for intra-community deliveries
     *  - G = tax not levied due to export outside the EU
     *  - O = Outside the tax scope
     *  - L = IGIC (Canary Islands)
     *  - M = IPSI (Ceuta / Melilla)
     * @param string $typeCode
     * In EN 16931 only the tax type “sales tax” with the code “VAT” is supported. Should other types of tax be
     * specified, such as an insurance tax or a mineral oil tax the EXTENDED profile must be used. The code for
     * the tax type must then be taken from the code list UNTDID 5153.
     * @param float $rateApplicablePercent
     * The VAT rate applicable to the item invoiced and expressed as a percentage. Note: The code of the sales
     * tax category and the category-specific sales tax rate  must correspond to one another. The value to be
     * given is the percentage. For example, the value 20 is given for 20% (and not 0.2)
     * @param float|null $calculatedAmount
     * Tax amount. Information only for taxes that are not VAT.
     * @param string|null $exemptionReason
     * Reason for tax exemption (free text)
     * @param string|null $exemptionReasonCode
     * Reason given in code form for the exemption of the amount from VAT. Note: Code list issued
     * and maintained by the Connecting Europe Facility.
     * @return OrderDocumentBuilder
     */
    public function addDocumentPositionTax(string $categoryCode, string $typeCode, float $rateApplicablePercent, ?float $calculatedAmount = null, ?string $exemptionReason = null, ?string $exemptionReasonCode = null): OrderDocumentBuilder
    {
        $tax = $this->objectHelper->getTradeTaxType($categoryCode, $typeCode, null, $calculatedAmount, $rateApplicablePercent, $exemptionReason, $exemptionReasonCode, null, null, null, null);
        $positionsettlement = $this->objectHelper->tryCallAndReturn($this->currentPosition, "getSpecifiedLineTradeSettlement");
        $this->objectHelper->tryCallAll($positionsettlement, ["addToApplicableTradeTax", "setApplicableTradeTax"], $tax);
        return $this;
    }

    /**
     * Add surcharges and discounts on position level
     *
     * @param float $actualAmount
     * The surcharge/discount amount excluding sales tax
     * @param boolean $isCharge
     * Switch that indicates whether the following data refer to an allowance or a discount,
     * true means that
     * @param float|null $calculationPercent
     * The percentage that may be used in conjunction with the base invoice line discount
     * amount to calculate the invoice line discount amount
     * @param float|null $basisAmount
     * The base amount that may be used in conjunction with the invoice line discount percentage
     * to calculate the invoice line discount amount
     * @param string|null $reasonCode
     * The reason given as a code for the invoice line discount
     *
     * __Notes__
     *  - Use entries from the UNTDID 5189 code list (discounts) or the UNTDID 7161 code list
     *    (surcharges). The invoice line discount reason code and the invoice line discount reason must
     *    match.
     *  - In the case of a discount, the code list UNTDID 5189 must be used.
     *  - In the event of a surcharge, the code list UNTDID 7161 must be used.
     *
     * In particular, the following codes can be used:
     *  - AA = Advertising
     *  - ABL = Additional packaging
     *  - ADR = Other services
     *  - ADT = Pick-up
     *  - FC = Freight service
     *  - FI = Financing
     *  - LA = Labelling
     *
     * Include PEPPOL subset:
     *  - 41 - Bonus for works ahead of schedule
     *  - 42 - Other bonus
     *  - 60 - Manufacturer’s consumer discount
     *  - 62 - Due to military status
     *  - 63 - Due to work accident
     *  - 64 - Special agreement
     *  - 65 - Production error discount
     *  - 66 - New outlet discount
     *  - 67 - Sample discount
     *  - 68 - End-of-range discount
     *  - 70 - Incoterm discount
     *  - 71 - Point of sales threshold allowance
     *  - 88 - Material surcharge/deduction
     *  - 95 - Discount
     *  - 100 - Special rebate
     *  - 102 - Fixed long term
     *  - 103 - Temporary
     *  - 104 - Standard
     *  - 105 - Yearly turnover
     *
     * Codelists: UNTDID 7161 (Complete list), UNTDID 5189 (Restricted)
     * @param string|null $reason
     * The reason given in text form for the invoice item discount/surcharge
     *
     * __Notes__
     *  - The invoice line discount reason code (BT-140) and the invoice line discount reason
     *    (BT-139) must show the same allowance type.
     *  - Each line item discount (BG-27) must include a corresponding line discount reason
     *    (BT-139) or an appropriate line discount reason code (BT-140), or both.
     *  - The code for the reason for the charge at the invoice line level (BT-145) and the
     *    reason for the invoice line discount (BT-144) must show the same discount type
     * @return OrderDocumentBuilder
     */
    public function addDocumentPositionAllowanceCharge(float $actualAmount, bool $isCharge, ?float $calculationPercent = null, ?float $basisAmount = null, ?string $reasonCode = null, ?string $reason = null): OrderDocumentBuilder
    {
        $positionsettlement = $this->objectHelper->tryCallAndReturn($this->currentPosition, "getSpecifiedLineTradeSettlement");
        $allowanceCharge = $this->objectHelper->getTradeAllowanceChargeType($actualAmount, $isCharge, null, null, null, null, $calculationPercent, $basisAmount, null, null, $reasonCode, $reason);
        $this->objectHelper->tryCall($positionsettlement, "addToSpecifiedTradeAllowanceCharge", $allowanceCharge);
        return $this;
    }

    /**
     * Set information on item totals
     *
     * @param float $lineTotalAmount
     * The total amount of the invoice item.
     * __Note:__ This is the "net" amount, that is, excluding sales tax, but including all surcharges
     * and discounts applicable to the item level, as well as other taxes.
     * @param float|null $totalAllowanceChargeAmount
     * Total amount of item surcharges and discounts
     * @return OrderDocumentBuilder
     */
    public function setDocumentPositionLineSummation(float $lineTotalAmount, ?float $totalAllowanceChargeAmount = null): OrderDocumentBuilder
    {
        $positionsettlement = $this->objectHelper->tryCallAndReturn($this->currentPosition, "getSpecifiedLineTradeSettlement");
        $summation = $this->objectHelper->getTradeSettlementLineMonetarySummationType($lineTotalAmount, $totalAllowanceChargeAmount);
        $this->objectHelper->tryCall($positionsettlement, "setSpecifiedTradeSettlementLineMonetarySummation", $summation);
        return $this;
    }

    /**
     * Set an AccountingAccount on item level
     * Detailinformationen zur Buchungsreferenz
     *
     * @param string $id
     * The unique identifier for this trade accounting account.
     * @param string|null $typeCode
     * The code specifying the type of trade accounting account, such as
     * general (main), secondary, cost accounting or budget account.
     * @return OrderDocumentBuilder
     */
    public function setDocumentPositionReceivableTradeAccountingAccount(string $id, ?string $typeCode = null): OrderDocumentBuilder
    {
        $positionsettlement = $this->objectHelper->tryCallAndReturn($this->currentPosition, "getSpecifiedLineTradeSettlement");
        $account = $this->objectHelper->getTradeAccountingAccountType($id, $typeCode);
        $this->objectHelper->tryCall($positionsettlement, "setReceivableSpecifiedTradeAccountingAccount", $account);
        return $this;
    }
}